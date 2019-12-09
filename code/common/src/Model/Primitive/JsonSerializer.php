<?php


namespace Gustav\Common\Model\Primitive;

/**
 * Class JsonSerializer
 * @package Gustav\Common\Model\Primitive
 */
class JsonSerializer extends PrimitiveSerializer
{
    /**
     * @param array $result
     * @return string
     */
    protected function encode(array $result): string
    {
        return json_encode($result, JSON_UNESCAPED_UNICODE|JSON_BIGINT_AS_STRING);
    }

    /**
     * @param string $stream
     * @return array
     */
    protected function decode(string $stream): array
    {
        return json_decode($stream, true);
    }
}