<?php


namespace Gustav\Common\Model\FlatBuffers;


use Google\FlatBuffers\ByteBuffer;

/**
 * Interface FlatBuffersInterface
 * @package Gustav\Common\Model
 */
interface FlatBuffersInterface extends FlatBuffersSerializable
{
    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return FlatBuffersInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): FlatBuffersInterface;
}
