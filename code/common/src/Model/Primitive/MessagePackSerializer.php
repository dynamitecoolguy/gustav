<?php


namespace Gustav\Common\Model\Primitive;

class MessagePackSerializer extends PrimitiveSerializer
{
    /**
     * @param array $result
     * @return string
     */
    protected function encode(array $result): string
    {
        return msgpack_serialize($result);
    }

    /**
     * @param string $stream
     * @return array
     */
    protected function decode(string $stream): array
    {
        return msgpack_unserialize($stream);
    }
}