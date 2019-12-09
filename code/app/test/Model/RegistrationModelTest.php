<?php


namespace Gustav\App\Model;

use Composer\Autoload\ClassLoader;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializer;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Gustav\Common\Model\Primitive\MessagePackSerializer;
use PHPUnit\Framework\TestCase;

class RegistrationModelTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../../flatbuffers/php');              // flatbuffers/php
    }

    /**
     * @param ModelSerializerInterface $serializer
     */
    private function encodeAndDecodeBody(ModelSerializerInterface $serializer)
    {
        $register1 = new RegistrationModel(1, 101, '');
        $register2 = new RegistrationModel(2, 102, 'hoge');

        $stream = $serializer->serialize([
            [1, 'req1', $register1],
            [1, 'req2', $register2]
        ]);

        $result = $serializer->deserialize($stream);

        $this->assertEquals(1, $result[0][2]->getUserId());
        $this->assertEquals(102, $result[1][2]->getOpenId());
        $this->assertEquals('', $result[0][2]->getCampaignCode());
        $this->assertEquals('hoge', $result[1][2]->getCampaignCode());
    }

    /**
     * @test
     * @throws ModelException
     */
    public function encodeAndDecodeFlatBuffers()
    {
        $this->encodeAndDecodeBody(new FlatBuffersSerializer());
    }

    /**
     * @test
     */
    public function encodeAndDecodeJson()
    {
        $this->encodeAndDecodeBody(new JsonSerializer());
    }

    /**
     * @test
     */
    public function encodeAndDecodeMessagePack()
    {
        $this->encodeAndDecodeBody(new MessagePackSerializer());
    }

    /**
     * @test
     */
    public function getterAndSetter()
    {
        $r = new RegistrationModel(3, 103, '333');

        $r->setUserId(4);
        $r->setOpenId(104);
        $r->setCampaignCode('444');

        $this->assertEquals(4, $r->getUserId());
        $this->assertEquals(104, $r->getOpenId());
        $this->assertEquals('444', $r->getCampaignCode());
    }
}
