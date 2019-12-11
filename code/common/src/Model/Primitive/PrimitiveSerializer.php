<?php


namespace Gustav\Common\Model\Primitive;

use \Exception;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelSerializerInterface;
use ReflectionClass;
use ReflectionException;

abstract class PrimitiveSerializer implements ModelSerializerInterface
{
    protected abstract function encode(array $result): string;
    protected abstract function decode(string $stream): array;

    /**
     * @inheritDoc
     */
    public function serialize(array $objectList): string
    {
        $result = [];

        // DataChunkのリストを作成する
        foreach ($objectList as $object) {
            $result[] = [
                $object->getChunkId(),
                $object->getVersion(),
                $object->getRequestId(),
                $object->getModel()->serializePrimitive()
            ];
        }

        return $this->encode($result);
    }

    /**
     * @inheritDoc
     */
    public function deserialize(string $stream): array
    {
        // 結果
        $objectList = [];

        foreach ($this->decode($stream) as $chunk) {
            list ($chunkId, $version, $requestId, $primitives) = $chunk;

            $className = ModelClassMap::findModelClass($chunkId);
            try {
                $refClass = new ReflectionClass($className);
                if (!$refClass->isSubclassOf(PrimitiveSerializable::class)) {
                    throw new ModelException("Class(${className} is not instance of PrimitiveSerializable");
                }
                $method = $refClass->getMethod('deserializePrimitive');
                $object = $method->invoke(null, $version, $primitives);
                if (!($object instanceof PrimitiveSerializable)) {
                    throw new ModelException('Deserialize result is not instanceof FlatBuffersSerializable');
                }
            } catch (ReflectionException $e) {
                throw new ModelException("Class(${className} could not create ReflectionClass");
            }

            $objectList[] = new ModelChunk($chunkId, $version, $requestId, $object);
        }

        return $objectList;
    }
}