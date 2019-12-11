<?php


namespace Gustav\App\Model;

use Composer\Autoload\ClassLoader;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializer;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Gustav\Common\Model\Primitive\MessagePackSerializer;
use PHPUnit\Framework\TestCase;

class IdentificationModelTest extends TestCase
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
     * @throws ModelException
     */
    private function encodeAndDecodeBody(ModelSerializerInterface $serializer)
    {
        $register1 = new IdentificationModel([
            IdentificationModel::USER_ID => 1,
            IdentificationModel::OPEN_ID => '101'
        ]);
        $register2 = new IdentificationModel([
            IdentificationModel::USER_ID => 2,
            IdentificationModel::OPEN_ID => '102',
            IdentificationModel::CAMPAIGN_CODE => 'hoge'
        ]);

        $stream = $serializer->serialize([
            new ModelChunk('REG', 3, 'req1', $register1),
            new ModelChunk('REG', 4, 'req2', $register2)
        ]);

        $result = $serializer->deserialize($stream);

        $this->assertEquals(1, $result[0]->getModel()->getUserId());
        $this->assertEquals('102', $result[1]->getModel()->getOpenId());
        $this->assertEquals('', $result[0]->getModel()->getCampaignCode());
        $this->assertEquals('hoge', $result[1]->getModel()->getCampaignCode());
        $this->assertEquals(3, $result[0]->getVersion());
        $this->assertEquals(4, $result[1]->getVersion());
        $this->assertEquals('REG', $result[0]->getChunkId());
        $this->assertEquals('req2', $result[1]->getRequestId());
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
     * @throws ModelException
     */
    public function encodeAndDecodeJson()
    {
        $this->encodeAndDecodeBody(new JsonSerializer());
    }

    /**
     * @test
     * @throws ModelException
     */
    public function encodeAndDecodeMessagePack()
    {
        $this->encodeAndDecodeBody(new MessagePackSerializer());
    }

    /**
     * @test
     * @throws ModelException
     */
    public function getterAndSetter()
    {
        $r = new IdentificationModel([
            IdentificationModel::USER_ID => 3,
            IdentificationModel::OPEN_ID => '103',
            IdentificationModel::CAMPAIGN_CODE => 'hoge'
        ]);

        $r->setUserId(4);
        $r->set(IdentificationModel::OPEN_ID, '104');
        $r->setCampaignCode('444');

        $this->assertEquals(4, $r->getUserId());
        $this->assertEquals('104', $r->getOpenId());
        $this->assertEquals('444', $r->get(IdentificationModel::CAMPAIGN_CODE));
    }
}
