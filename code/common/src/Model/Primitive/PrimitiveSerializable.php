<?php


namespace Gustav\Common\Model\Primitive;


interface PrimitiveSerializable
{
    /**
     * デシリアル化
     * @param int $version
     * @param array $primitives
     * @return PrimitiveSerializable
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable;

    /**
     * シリアル化
     * @return array
     */
    public function serializePrimitive(): array;
}