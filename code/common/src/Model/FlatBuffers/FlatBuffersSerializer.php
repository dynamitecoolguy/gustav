<?php


namespace Gustav\Common\Model\FlatBuffers;

use Exception;
use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelSerializerInterface;

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
     * @param array $objectList   [[version(int), requestId(string), object(FlatBuffersInterface)]]
     * @return string
     * @throws ModelException
     */
    public function serialize(array $objectList): string
    {
        $builder = new FlatbufferBuilder(self::INITIAL_TOTAL_BUFFER_SIZE);

        // 各オブジェクトの識別コード (一度登録した識別コードは再利用する)
        $chunkIdMap = [];
        $chunkList = [];

        // DataChunkのリストを作成する
        foreach ($objectList as [$version, $requestId, $object]) {
            /** @var FlatBuffersInterface $object */

            $chunkId = ModelClassMap::findChunkId(get_class($object));
            if (!isset($chunkIdMap[$chunkId])) {
                $chunkIdMap[$chunkId] = $builder->createString($chunkId);
            }
            $requestIdPos = $builder->createString($requestId);

            $chunkList[] = DataChunk::createDataChunk(
                $builder,
                $chunkIdMap[$chunkId],                                                  // id
                $version,
                $requestIdPos,                                                          // requestId
                DataChunk::createContentVector($builder, self::serializeModel($object)) // content
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
     * @param string $stream
     * @return array  [[version(int), requestId(string), object(FlatBuffersInterface)]]
     * @throws ModelException
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
            $version = $chunk->getVersion();
            $requestId = $chunk->getRequestId();
            $content = $chunk->getContentBytes();

            $className = ModelClassMap::findModelClass($chunkId);

            try {
                // FlatBuffersInterface::deserialize(int $version, ByteBuffer $buffer): ModelInterfaceの呼び出し
                $object = call_user_func([$className, 'deserialize'], $version, ByteBuffer::wrap($content));
                if (!($object instanceof FlatBuffersInterface)) {
                    throw new ModelException('Deserialize result is not instanceof FlatBuffersInterface');
                }
            } catch (Exception $e) {
                throw new ModelException('Deserialize Error Reason:' . $e->getMessage(), 0, $e);
            }

            $objectList[] = [$version, $requestId, $object];
        }

        return $objectList;
    }

    /**
     * オブジェクトをバイナリ化する
     * @param FlatBuffersInterface $object
     * @return int[]
     */
    protected static function serializeModel(FlatBuffersInterface $object): array
    {
        $builder = new FlatbufferBuilder(self::INITIAL_MODEL_BUFFER_SIZE);

        $pos = $object->serialize($builder);
        $builder->finish($pos);

        return array_values(unpack('C*', $builder->sizedByteArray()));
    }
}
