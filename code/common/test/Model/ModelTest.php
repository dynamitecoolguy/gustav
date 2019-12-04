<?php


namespace Gustav\Common\Model;

use Composer\Autoload\ClassLoader;
use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Exception\ModelException;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../../flatbuffers/example/php');              // flatbuffers/php
    }

    /**
     * @test
     */
    public function singleMonster()
    {
        ModelClassMap::registerModel('MON', MonsterModel::class);

        $monster = new MonsterModel();
        $monster->name = 'single';
        $monster->hp = 123;

        $stream = ModelSerializer::serialize([[1, 'req', $monster]]);

        $result = ModelSerializer::deserialize($stream);

        $this->assertIsArray($result);

        $version = $result[0][0];
        $requestId = $result[0][1];
        $resultMonster = $result[0][2];

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

        $stream = ModelSerializer::serialize([[1, 'req1', $monster1], [2, 'req2', $monster2], [3, 'req3', $monster3]]);

        $result = ModelSerializer::deserialize($stream);

        $this->assertIsArray($result);

        $resultMonster1 = $result[0][2];
        $resultMonster2 = $result[1][2];
        $resultMonster3 = $result[2][2];

        $resultId1 = $result[0][1];
        $resultId2 = $result[1][1];
        $resultId3 = $result[2][1];

        $version1 = $result[0][0];
        $version2 = $result[1][0];
        $version3 = $result[2][0];

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
        ModelClassMap::registerModel('MON', MonsterModel::class);

        $stream = ModelSerializer::serialize([]);

        $result = ModelSerializer::deserialize($stream);

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
        ModelClassMap::registerModel('MON', MonsterModel::class);
        ModelClassMap::registerModel('MON2', AnotherMonsterModel::class);

        $this->expectException(ModelException::class);

        $monster = new AnotherMonsterModel();
        $monster->name = 'noone';
        $monster->hp = 0;

        $stream = ModelSerializer::serialize([[0, 'req', $monster]]);
        ModelSerializer::deserialize($stream);
    }
}

class DuplicatedChunkIdModel implements ModelInterface
{
    public function serialize(FlatbufferBuilder &$builder): int {return $builder->offset(); }
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface { return null;}
}

class AnotherMonsterModel extends MonsterModel
{
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface {
        throw new \Exception();
    }

}