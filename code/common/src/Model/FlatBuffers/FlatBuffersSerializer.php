<?php


namespace Gustav\Common\Model\FlatBuffers;

use Exception;
use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelSerializerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Class FlatBuffersSerializer
 * @package Gustav\Common\Model
 *
 * table DataChunk {
 *   id: string;
 *   version: ubyte;
 *   content: [ubyte];
 * }
 * table DataModel {
 *   chunk: [DataChunk];
 * }
 */
class FlatBuffersSerializer implements ModelSerializerInterface
{
    /**
     * 全体バッファの初期サイズ
     */
    const INITIAL_TOTAL_BUFFER_SIZE = 2048;

    /**
     * 個別のモデルオブジェクトバッファの初期サイズ
     */
    const INITIAL_MODEL_BUFFER_SIZE = 512;

    /**
     * @inheritDoc
     */
    public function serialize(array $objectList): string
    {
        $builder = new FlatbufferBuilder(self::INITIAL_TOTAL_BUFFER_SIZE);

        // 各オブジェクトの識別コード (一度登録した識別コードは再利用する)
        $chunkIdMap = [];
        $chunkList = [];

        // DataChunkのリストを作成する
        foreach ($objectList as $object) {
            $chunkId = $object->getChunkId();
            $model = $object->getModel(); /** @var FlatBuffersSerializable $model */
            $requestId = $object->getRequestId();

            if (!isset($chunkIdMap[$chunkId])) {
                $chunkIdMap[$chunkId] = $builder->createString($chunkId);
            }
            $requestIdPos = $builder->createString($requestId);

            $chunkList[] = DataChunk::createDataChunk(
                $builder,
                $chunkIdMap[$chunkId],                                                  // id
                $object->getVersion(),
                $requestIdPos,                                                          // requestId
                DataChunk::createContentVector($builder, self::serializeModel($model))  // content
            );
        }

        // DataModelを作成する
        $chunkListVector = DataModel::createChunkVector($builder, $chunkList);
        $dataModel = DataModel::createDataModel($builder, $chunkListVector);

        // 作成終了
        $builder->finish($dataModel);

        // 必要なところだけカットして返す
        return $builder->sizedByteArray();
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $stream): array
    {
        // 結果
        $objectList = [];

        // DataModelの取得
        $buffer = ByteBuffer::wrap($stream);
        $dataModel = DataModel::getRootAsDataModel($buffer);

        // DataModel内の各DataChunkの処理
        $chunkLength = $dataModel->getChunkLength();
        for ($i = 0; $i < $chunkLength; $i++) {
            $chunk = $dataModel->getChunk($i);

            $chunkId = $chunk->getChunkId();
            $version = (int)$chunk->getVersion();
            $requestId = $chunk->getRequestId();
            $content = $chunk->getContentBytes();

            $className = ModelClassMap::findModelClass($chunkId);
            try {
                $refClass = new ReflectionClass($className);
                if (!$refClass->isSubclassOf(FlatBuffersSerializable::class)) {
                    throw new ModelException("Class(${className} is not instance of FlatBuffersSerializable");
                }
                $method = $refClass->getMethod('deserializeFlatBuffers');
                $object = $method->invoke(null, $version, ByteBuffer::wrap($content));
                if (!($object instanceof FlatBuffersSerializable)) {
                    throw new ModelException('Deserialize result is not instance of FlatBuffersSerializable');
                }
            } catch (ReflectionException $e) {
                throw new ModelException("Class(${className} could not create ReflectionClass");
            }

            $objectList[] = new ModelChunk($chunkId, $version, $requestId, $object);
        }

        return $objectList;
    }

    /**
     * オブジェクトをバイナリ化する
     * @param FlatBuffersSerializable $object
     * @return int[]
     */
    protected static function serializeModel(FlatBuffersSerializable $object): array
    {
        $builder = new FlatbufferBuilder(self::INITIAL_MODEL_BUFFER_SIZE);

        $pos = $object->serializeFlatBuffers($builder);
        $builder->finish($pos);

        return array_values(unpack('C*', $builder->sizedByteArray()));
    }
}
