<?php


namespace Gustav\Common\Model;


use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializer;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Gustav\Common\Model\Primitive\MessagePackSerializer;
use PHPUnit\Framework\TestCase;

class ModelSerializeFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function flatBufferSerializer()
    {
        $config = new class implements ApplicationConfigInterface {
            public function getValue(string $category, string $key, ?string $default = null): string
            {
                if ($category == 'serializer' && $key == 'type') {
                    return 'flatbuffers';
                }
                return $default;
            }
        };
        $this->assertInstanceOf(FlatBuffersSerializer::class, ModelSerializerFactory::create($config));
    }

    /**
     * @test
     */
    public function jsonSerializer()
    {
        $config = new class implements ApplicationConfigInterface {
            public function getValue(string $category, string $key, ?string $default = null): string
            {
                if ($category == 'serializer' && $key == 'type') {
                    return 'json';
                }
                return $default;
            }
        };
        $this->assertInstanceOf(JsonSerializer::class, ModelSerializerFactory::create($config));
    }

    /**
     * @test
     */
    public function msgpackSerializer()
    {
        $config = new class implements ApplicationConfigInterface {
            public function getValue(string $category, string $key, ?string $default = null): string
            {
                if ($category == 'serializer' && $key == 'type') {
                    return 'msgpack';
                }
                return $default;
            }
        };
        $this->assertInstanceOf(MessagePackSerializer::class, ModelSerializerFactory::create($config));
    }

    /**
     * @test
     */
    public function invalidSerializer()
    {
        $this->expectException(ConfigException::class);

        $config = new class implements ApplicationConfigInterface {
            public function getValue(string $category, string $key, ?string $default = null): string
            {
                if ($category == 'serializer' && $key == 'type') {
                    return 'unknown';
                }
                return $default;
            }
        };
        ModelSerializerFactory::create($config);
    }
}