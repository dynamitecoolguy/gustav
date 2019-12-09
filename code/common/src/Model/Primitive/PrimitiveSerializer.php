<?php


namespace Gustav\Common\Model\Primitive;

use \Exception;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelSerializerInterface;

abstract class PrimitiveSerializer implements ModelSerializerInterface
{
    protected abstract function encode(array $result): string;
    protected abstract function decode(string $stream): array;

    /**
     * @inheritDoc
     * @throws ModelException
     */
    public function serialize(array $objectList): string
    {
        $result = [];

        // DataChunkのリストを作成する
        foreach ($objectList as [$version, $requestId, $object]) {
            /** @var PrimitiveSerializable $object */

            $result[] = [
                ModelClassMap::findChunkId(get_class($object)),
                $version,
                $requestId,
                $object->serializePrimitive()
            ];
        }

        return $this->encode($result);
    }

    /**
     * @inheritDoc
     * @throws ModelException
     */
    public function deserialize(string $stream): array
    {
        // 結果
        $objectList = [];

        foreach ($this->decode($stream) as $chunk) {
            list ($chunkId, $version, $requestId, $primitives) = $chunk;

            $className = ModelClassMap::findModelClass($chunkId);

            try {
                $object = call_user_func([$className, 'deserializePrimitive'], $version, $primitives);
                if (!($object instanceof PrimitiveSerializable)) {
                    throw new ModelException('Deserialize result is not instanceof FlatBuffersSerializable');
                }
            } catch (Exception $e) {
                throw new ModelException('Deserialize Error Reason:' . $e->getMessage(), 0, $e);
            }

            $objectList[] = [$version, $requestId, $object];
        }

        return $objectList;
    }
}