<?php


namespace Gustav\Common\Model;


use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializer;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Gustav\Common\Model\Primitive\MessagePackSerializer;

/**
 * Class ModelSerializerFactory
 * @package Gustav\Common\Model
 */
class ModelSerializerFactory
{
    /**
     * @param ApplicationConfigInterface $config
     * @return ModelSerializerInterface
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): ModelSerializerInterface
    {
        $serializerType = strtolower($config->getValue('serializer', 'type'));

        if ($serializerType == 'flatbuffers') {
            return new FlatBuffersSerializer();
        } elseif ($serializerType == 'json') {
            return new JsonSerializer();
        } elseif ($serializerType == 'msgpack' || $serializerType == 'messagepack') {
            return new MessagePackSerializer();
        }
        throw new ConfigException("serializer.type is unknown type(${serializerType})");
    }

}