<?php


namespace Gustav\Common\Model\Primitive;


use Gustav\Common\Exception\ModelException;

interface PrimitiveSerializable
{
    /**
     * デシリアル化
     * @param int $version
     * @param array $primitives
     * @return PrimitiveSerializable
     * @throws ModelException
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable;

    /**
     * シリアル化
     * @return array
     */
    public function serializePrimitive(): array;
}