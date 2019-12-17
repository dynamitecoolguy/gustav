<?php


namespace Gustav\Common\Model\Primitive;

use Composer\Autoload\ClassLoader;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\MonsterModel;
use PHPUnit\Framework\TestCase;

class JsonModelTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../../vendor/autoload.php';
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

        $serializer = new JsonSerializer();

        $stream = $serializer->serialize(new Parcel('tt', [new Pack('MON', 1, 'req', $monster)]));

        $result = $serializer->deserialize($stream);

        $this->assertInstanceOf(Parcel::class, $result);
        $packList = $result->getPackList();

        $chunkId = $packList[0]->getPackType();
        $version = $packList[0]->getVersion();
        $requestId = $packList[0]->getRequestId();
        $resultMonster = $packList[0]->getModel();

        $this->assertEquals('tt', $result->getToken());
        $this->assertEquals('MON', $chunkId);
        $this->assertEquals(1, $version);
        $this->assertEquals('req', $requestId);
        $this->assertFalse($monster === $resultMonster);
        $this->assertEquals('single', $resultMonster->name);
        $this->assertEquals(123, $resultMonster->hp);
    }
}
