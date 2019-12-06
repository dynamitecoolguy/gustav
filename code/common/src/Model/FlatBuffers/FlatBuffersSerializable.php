<?php


namespace Gustav\Common\Model\FlatBuffers;


use Google\FlatBuffers\FlatbufferBuilder;

/**
 * Interface FlatBuffersSerializable
 * @package Gustav\Common\Model
 */
interface FlatBuffersSerializable
{
    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return int table_offset
     */
    public function serialize(FlatbufferBuilder &$builder): int;
}
