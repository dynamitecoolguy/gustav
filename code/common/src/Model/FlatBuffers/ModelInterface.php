<?php


namespace Gustav\Common\Model\FlatBuffers;


use Google\FlatBuffers\ByteBuffer;

/**
 * Interface ModelInterface
 * @package Gustav\Common\Model
 */
interface ModelInterface extends ModelSerializable
{
    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return ModelInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface;
}
