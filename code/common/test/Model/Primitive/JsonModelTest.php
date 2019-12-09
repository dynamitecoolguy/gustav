<?php


namespace Gustav\Common\Model\Primitive;

use Composer\Autoload\ClassLoader;
use Gustav\Common\Model\ModelClassMap;
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
        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);

        $monster = new MonsterModel();
        $monster->name = 'single';
        $monster->hp = 123;

        $serializer = new JsonSerializer();

        $stream = $serializer->serialize([[1, 'req', $monster]]);

        $result = $serializer->deserialize($stream);

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

}
