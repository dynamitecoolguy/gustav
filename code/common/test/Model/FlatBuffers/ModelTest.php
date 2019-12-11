<?php


namespace Gustav\Common\Model\FlatBuffers;

use Composer\Autoload\ClassLoader;
use Google\FlatBuffers\ByteBuffer;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelClassMap;
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
        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);

        $monster = new MonsterModel();
        $monster->name = 'single';
        $monster->hp = 123;

        $serializer = new FlatBuffersSerializer();

        $stream = $serializer->serialize([new ModelChunk('MON', 1, 'req', $monster)]);

        $result = $serializer->deserialize($stream);

        $this->assertIsArray($result);

        $chunkId = $result[0]->getChunkId();
        $version = $result[0]->getVersion();
        $requestId = $result[0]->getRequestId();
        $resultMonster = $result[0]->getModel();

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
        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);

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
        $stream = $serializer->serialize([
            new ModelChunk('MON', 1, 'req1', $monster1),
            new ModelChunk('MON', 2, 'req2', $monster2),
            new ModelChunk('MON', 3, 'req3', $monster3)
        ]);
        $result = $serializer->deserialize($stream);

        $this->assertIsArray($result);

        $resultMonster1 = $result[0]->getModel();
        $resultMonster2 = $result[1]->getModel();
        $resultMonster3 = $result[2]->getModel();

        $resultId1 = $result[0]->getRequestId();
        $resultId2 = $result[1]->getRequestId();
        $resultId3 = $result[2]->getRequestId();

        $version1 = $result[0]->getVersion();
        $version2 = $result[1]->getVersion();
        $version3 = $result[2]->getVersion();

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
        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);

        $serializer = new FlatBuffersSerializer();
        $stream = $serializer->serialize([]);
        $result = $serializer->deserialize($stream);

        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));
    }

    /**
     * @test
     * @throws ModelException
     */
    public function registerDuplicated()
    {
        $this->expectException(ModelException::class);

        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);
        ModelClassMap::registerModel('MON', DuplicatedChunkIdModel::class);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function notFound()
    {
        $this->expectException(ModelException::class);

        ModelClassMap::findModelClass('NOTFOUND');
    }

    /**
     * @test
     * @throws ModelException
     */
    public function deserializeFailed()
    {
        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);
        ModelClassMap::registerModel('MON2', AnotherMonsterModel::class);

        $this->expectException(ModelException::class);
        $this->expectException(\Exception::class);

        $monster = new AnotherMonsterModel();
        $monster->name = 'noone';
        $monster->hp = 0;

        $serializer = new FlatBuffersSerializer();
        $stream = $serializer->serialize([new ModelChunk('MON2', 0, 'req', $monster)]);
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