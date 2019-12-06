<?php


namespace Gustav\Common\Model\FlatBuffers;


use Google\FlatBuffers\FlatbufferBuilder;

/**
 * Interface ModelSerializable
 * @package Gustav\Common\Model
 */
interface ModelSerializable
{
    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return int table_offset
     */
    public function serialize(FlatbufferBuilder &$builder): int;
}
