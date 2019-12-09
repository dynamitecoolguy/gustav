<?php


namespace Gustav\Common\Model\FlatBuffers;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;

/**
 * Interface FlatBuffersSerializable
 * @package Gustav\Common\Model
 */
interface FlatBuffersSerializable
{
    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return FlatBuffersSerializable
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable;

    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return int table_offset
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int;
}
