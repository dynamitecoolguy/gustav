<?php


namespace Gustav\Common\Model\FlatBuffers;

use Composer\Autoload\ClassLoader;
use Google\FlatBuffers\ByteBuffer;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Model\MonsterModel;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../../../flatbuffers/example/php');              // flatbuffers/php
    }

    /**
     * @test
     */
    public function singleMonster()
    {
        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);

        $monster = new MonsterModel();
        $monster->name = 'single';
        $monster->hp = 123;

        $serializer = new FlatBuffersSerializer();

        $stream = $serializer->serialize(new Parcel("tok", [new Pack('MON', 1, 'req', $monster)]));

        $result = $serializer->deserialize($stream);

        $this->assertInstanceOf(Parcel::class, $result);

        $packList = $result->getPackList();

        $chunkId = $packList[0]->getPackType();
        $version = $packList[0]->getVersion();
        $requestId = $packList[0]->getRequestId();
        $resultMonster = $packList[0]->getModel();

        $this->assertEquals('MON', $chunkId);
        $this->assertEquals(1, $version);
        $this->assertEquals('req', $requestId);
        $this->assertFalse($monster === $resultMonster);
        $this->assertEquals('single', $resultMonster->name);
        $this->assertEquals(123, $resultMonster->hp);
    }

    /**
     * @test
     */
    public function tripleMonster()
    {
        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);

        $monster1 = new MonsterModel();
        $monster1->name = 'gaia';
        $monster1->hp = 111;

        $monster2 = new MonsterModel();
        $monster2->name = 'ortega';
        $monster2->hp = 222;

        $monster3 = new MonsterModel();
        $monster3->name = 'mash';
        $monster3->hp = 333;

        $serializer = new FlatBuffersSerializer();
        $stream = $serializer->serialize(
            new Parcel("t",
                [
                    new Pack('MON', 1, 'req1', $monster1),
                    new Pack('MON', 2, 'req2', $monster2),
                    new Pack('MON', 3, 'req3', $monster3)
                ])
        );
        $result = $serializer->deserialize($stream);

        $this->assertInstanceOf(Parcel::class, $result);

        $packList = $result->getPackList();

        $resultMonster1 = $packList[0]->getModel();
        $resultMonster2 = $packList[1]->getModel();
        $resultMonster3 = $packList[2]->getModel();

        $resultId1 = $packList[0]->getRequestId();
        $resultId2 = $packList[1]->getRequestId();
        $resultId3 = $packList[2]->getRequestId();

        $version1 = $packList[0]->getVersion();
        $version2 = $packList[1]->getVersion();
        $version3 = $packList[2]->getVersion();

        $this->assertEquals(1, $version1);
        $this->assertEquals(2, $version2);
        $this->assertEquals(3, $version3);
        $this->assertEquals('req1', $resultId1);
        $this->assertEquals('req2', $resultId2);
        $this->assertEquals('req3', $resultId3);

        $this->assertEquals(111, $resultMonster1->hp);
        $this->assertEquals(222, $resultMonster2->hp);
        $this->assertEquals(333, $resultMonster3->hp);
    }

    /**
     * @test
     */
    public function empty()
    {
        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);

        $serializer = new FlatBuffersSerializer();
        $stream = $serializer->serialize(new Parcel('', []));
        $result = $serializer->deserialize($stream);

        $this->assertInstanceOf(Parcel::class, $result);
        $this->assertEquals(0, sizeof($result->getPackList()));
    }

    /**
     * @test
     * @throws ModelException
     */
    public function registerDuplicated()
    {
        $this->expectException(ModelException::class);

        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);
        ModelMapper::registerModel('MON', DuplicatedChunkIdModel::class);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function notFound()
    {
        $this->expectException(ModelException::class);

        ModelMapper::findModelClass('NOTFOUND');
    }

    /**
     * @test
     * @throws ModelException
     */
    public function deserializeFailed()
    {
        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);
        ModelMapper::registerModel('MON2', AnotherMonsterModel::class);

        $this->expectException(ModelException::class);
        $this->expectException(\Exception::class);

        $monster = new AnotherMonsterModel();
        $monster->name = 'noone';
        $monster->hp = 0;

        $serializer = new FlatBuffersSerializer();
        $stream = $serializer->serialize(new Parcel('x', [new Pack('MON2', 0, 'req', $monster)]));
        $serializer->deserialize($stream);
    }
}

class DuplicatedChunkIdModel implements ModelInterface {}

class AnotherMonsterModel extends MonsterModel
{
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable {
        throw new \Exception();
    }

}