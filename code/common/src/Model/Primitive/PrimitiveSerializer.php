<?php


namespace Gustav\Common\Model\Primitive;

use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelSerializerInterface;
use ReflectionClass;
use ReflectionException;

/**
 * 単純配列とバイナリの相互変換を行います
 * Class PrimitiveSerializer
 * @package Gustav\Common\Model\Primitive
 */
abstract class PrimitiveSerializer implements ModelSerializerInterface
{
    protected abstract function encode(array $result): string;
    protected abstract function decode(string $stream): array;

    /**
     * @inheritDoc
     */
    public function serialize(Parcel $parcel): string
    {
        $result = [];

        // Packのリストを作成する
        foreach ($parcel->getPackList() as $object) {
            $result[] = [
                $object->getPackType(),
                $object->getVersion(),
                $object->getRequestId(),
                $object->getModel()->serializePrimitive()
            ];
        }

        // 最後にアクセストークンを追加する
        $result[] = $parcel->getToken();

        return $this->encode($result);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $stream): Parcel
    {
        // 結果
        $packList = [];

        $decoded = $this->decode($stream);

        // 最後の要素(アクセストークン)を取り除きます
        $token = array_pop($decoded);

        // 残りの要素をPackに変換します
        foreach ($decoded as $pack) {
            list ($packType, $version, $requestId, $primitives) = $pack;

            // packTypeに対応するmodelのクラス名を取得
            $className = ModelMapper::findModelClass($packType);
            try {
                // クラスのdeserializePrimitiveクラスメソッドを呼び出します
                $refClass = new ReflectionClass($className);
                if (!$refClass->isSubclassOf(PrimitiveSerializable::class)) {
                    throw new ModelException(
                        "Class(${className} is not instance of PrimitiveSerializable",
                        ModelException::CLASS_HAS_NOT_ADAPTED_INTERFACE
                    );
                }
                $method = $refClass->getMethod('deserializePrimitive');
                $object = $method->invoke(null, $version, $primitives);
            } catch (ReflectionException $e) {
                throw new ModelException(
                    "Class(${className} could not create ReflectionClass",
                    ModelException::REFLECTION_EXCEPTION_OCCURRED,
                    $e
                );
            }

            $packList[] = new Pack($packType, $version, $requestId, $object);
        }

        return new Parcel($token, $packList);
    }
}