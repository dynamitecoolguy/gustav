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
        $token = array_pop($decoded);

        foreach ($decoded as $pack) {
            list ($packType, $version, $requestId, $primitives) = $pack;

            $className = ModelMapper::findModelClass($packType);
            try {
                $refClass = new ReflectionClass($className);
                if (!$refClass->isSubclassOf(PrimitiveSerializable::class)) {
                    throw new ModelException(
                        "Class(${className} is not instance of PrimitiveSerializable",
                        ModelException::CLASS_HAS_NOT_ADAPTED_INTERFACE
                    );
                }
                $method = $refClass->getMethod('deserializePrimitive');
                $object = $method->invoke(null, $version, $primitives);
                if (!($object instanceof PrimitiveSerializable)) {
                    throw new ModelException(
                        'Deserialize result is not instanceof FlatBuffersSerializable',
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

            $packList[] = new Pack($packType, $version, $requestId, $object);
        }

        return new Parcel($token, $packList);
    }
}