<?php


namespace Gustav\Common\Model\FlatBuffers;

use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelSerializerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Class FlatBuffersSerializer
 * @package Gustav\Common\Model
 *
 * table Pack {
 *   packType: string;
 *   version: ubyte;
 *   requestId: string;
 *   content: [ubyte];
 * }
 *
 * table Parcel {
 *   token: string;
 *   pack: [Pack];
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
    public function serialize(Parcel $parcel): string
    {
        /** @noinspection PhpParamsInspection */
        $builder = new FlatbufferBuilder(self::INITIAL_TOTAL_BUFFER_SIZE);

        // 各オブジェクトの識別コード (一度登録した識別コードは再利用する)
        $packTypeMap = [];
        $packList = [];

        // Packのリストを作成する
        foreach ($parcel->getPackList() as $object) {
            $packType = $object->getPackType();
            $model = $object->getModel(); /** @var FlatBuffersSerializable $model */
            $requestId = $object->getRequestId();

            if (!isset($packTypeMap[$packType])) {
                $packTypeMap[$packType] = $builder->createString($packType);
            }
            $requestIdPos = $builder->createString($requestId);

            $packList[] = FlatBuffersPack::createFlatBuffersPack(
                $builder,
                $packTypeMap[$packType],                                                        // packType
                $object->getVersion(),                                                        // version
                $requestIdPos,                                                                // requestId
                FlatBuffersPack::createContentVector($builder, self::serializeModel($model))  // content
            );
        }

        // Parcelを作成する
        $packVector = FlatBuffersParcel::createPackVector($builder, $packList);
        $tokenPos = $builder->createString($parcel->getToken());
        $parcel = FlatBuffersParcel::createFlatBuffersParcel($builder, $tokenPos, $packVector);

        // 作成終了
        /** @noinspection PhpParamsInspection */
        $builder->finish($parcel);

        // 必要なところだけカットして返す
        return $builder->sizedByteArray();
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $stream): Parcel
    {
        // 結果
        $objectList = [];

        // Parcelの取得
        $buffer = ByteBuffer::wrap($stream);
        $parcel = FlatBuffersParcel::getRootAsFlatBuffersParcel($buffer);
        $token = $parcel->getToken();

        // Parcel内の各Packetの処理
        $packSize = $parcel->getPackLength();
        for ($i = 0; $i < $packSize; $i++) {
            $pack = $parcel->getPack($i);

            $packType = $pack->getPackType();
            $version = (int)$pack->getVersion();
            $requestId = $pack->getRequestId();
            $content = $pack->getContentBytes();

            $className = ModelMapper::findModelClass($packType);
            try {
                $refClass = new ReflectionClass($className);
                if (!$refClass->isSubclassOf(FlatBuffersSerializable::class)) {
                    throw new ModelException(
                        "Class(${className} is not instance of FlatBuffersSerializable",
                        ModelException::CLASS_HAS_NOT_ADAPTED_INTERFACE
                    );
                }
                $method = $refClass->getMethod('deserializeFlatBuffers');
                $object = $method->invoke(null, $version, ByteBuffer::wrap($content));
                if (!($object instanceof FlatBuffersSerializable)) {
                    throw new ModelException(
                        'Deserialize result is not instance of FlatBuffersSerializable',
                        ModelException::CLASS_HAS_NOT_ADAPTED_INTERFACE
                    );
                }
            } catch (ReflectionException $e) {
                throw new ModelException(
                    "Class(${className} could not create ReflectionClass",
                    ModelException::REFLECTION_EXCEPTION_OCCURRED,
                    $e
                );
            }

            $objectList[] = new Pack($packType, $version, $requestId, $object);
        }

        return new Parcel($token, $objectList);
    }

    /**
     * オブジェクトをバイナリ化する
     * @param FlatBuffersSerializable $object
     * @return int[]
     */
    protected static function serializeModel(FlatBuffersSerializable $object): array
    {
        /** @noinspection PhpParamsInspection */
        $builder = new FlatbufferBuilder(self::INITIAL_MODEL_BUFFER_SIZE);

        $pos = $object->serializeFlatBuffers($builder);

        /** @noinspection PhpParamsInspection */
        $builder->finish($pos);

        return array_values(unpack('C*', $builder->sizedByteArray()));
    }
}
