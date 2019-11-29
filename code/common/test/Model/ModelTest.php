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
        $autoloader->addPsr4('', __DIR__ . '/../../../flatbuffers/php');              // flatbuffers/php
    }

    /**
     * @test
     */
    public function singleMonster()
    {
        ModelClassMap::registerModel(Monster::class);

        $monster = new Monster();
        $monster->name = 'single';
        $monster->hp = 123;

        $stream = ModelSerializer::serialize([$monster]);

        $result = ModelSerializer::deserialize($stream);

        $this->assertIsArray($result);

        $resultMonster = $result[0];

        $this->assertFalse($monster === $resultMonster);
        $this->assertEquals('single', $resultMonster->name);
        $this->assertEquals(123, $resultMonster->hp);
    }

    /**
     * @test
     */
    public function tripleMonster()
    {
        ModelClassMap::registerModel(Monster::class);

        $monster1 = new Monster();
        $monster1->name = 'gaia';
        $monster1->hp = 111;

        $monster2 = new Monster();
        $monster2->name = 'ortega';
        $monster2->hp = 222;

        $monster3 = new Monster();
        $monster3->name = 'mash';
        $monster3->hp = 333;

        $stream = ModelSerializer::serialize([$monster1, $monster2, $monster3]);

        $result = ModelSerializer::deserialize($stream);

        $this->assertIsArray($result);

        $resultMonster1 = $result[0];
        $resultMonster2 = $result[1];
        $resultMonster3 = $result[2];

        $this->assertEquals(111, $resultMonster1->hp);
        $this->assertEquals(222, $resultMonster2->hp);
        $this->assertEquals(333, $resultMonster3->hp);
    }

    /**
     * @test
     */
    public function empty()
    {
        ModelClassMap::registerModel(Monster::class);

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

        ModelClassMap::registerModel(Monster::class);
        ModelClassMap::registerModel(DuplicatedChunkIdModel::class);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function registerFailed()
    {
        $this->expectException(ModelException::class);

        ModelClassMap::registerModel(ModelTest::class);
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
        ModelClassMap::registerModel(Monster::class);
        ModelClassMap::registerModel(AnotherMonster::class);

        $this->expectException(ModelException::class);

        $monster = new AnotherMonster();
        $monster->name = 'noone';
        $monster->hp = 0;

        $stream = ModelSerializer::serialize([$monster]);
        ModelSerializer::deserialize($stream);
    }
}

class DuplicatedChunkIdModel implements ModelInterface
{
    public static function chunkId(): string { return 'MON'; }
    public static function formatVersion(): int { return 1; }
    public function serialize(FlatbufferBuilder &$builder): void {}
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface { return null;}
}

class AnotherMonster extends Monster
{
    public static function chunkId(): string { return 'MON2'; }
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface {
        throw new \Exception();
    }

}