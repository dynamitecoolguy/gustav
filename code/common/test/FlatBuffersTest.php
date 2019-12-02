<?php


namespace Gustav\Common;

use Composer\Autoload\ClassLoader;
use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use MyGame\Sample\Color;
use MyGame\Sample\Equipment;
use MyGame\Sample\Monster;
use MyGame\Sample\Vec3;
use MyGame\Sample\Weapon;
use PHPUnit\Framework\TestCase;

class FlatBuffersTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../flatbuffers/example/php');              // flatbuffers/php
    }

    /**
     * @test
     */
    public function encode()
    {
        $builder = new FlatbufferBuilder(4096);
        $names = [
            'Sword' => $builder->createString('Sword'),
            'Axe' => $builder->createString('Axe'),
            'Orc' => $builder->createString('Orc')
        ];
        $this->addMonster($builder, $names, 300);

        //$buffer = $builder->dataBuffer();
        $bytes = $builder->sizedByteArray();
        $buffer = ByteBuffer::wrap($bytes);

        $monster = Monster::getRootAsMonster($buffer);

        $this->assertEquals(1.0, $monster->getPos()->GetX());
        $this->assertEquals(300, $monster->getHp());
        $this->assertEquals('Orc', $monster->getName());
        $this->assertEquals(Color::Red, $monster->getColor());
        $this->assertEquals('Sword', $monster->getEquipped(new Weapon())->getName());
    }

    private function addMonster(FlatbufferBuilder $builder, $names, $hp)
    {
        $sword = Weapon::createWeapon($builder, $names['Sword'], 3);
        $axe = Weapon::createWeapon($builder, $names['Axe'], 5);

        $treasure = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inv = Monster::createInventoryVector($builder, $treasure);

        $weapons = Monster::createWeaponsVector($builder, [$sword, $axe]);

        $pos = Vec3::createVec3($builder, 1.0, 2.0, 3.0);

        Monster::startMonster($builder);;
        Monster::addPos($builder, $pos);
        Monster::addHp($builder, $hp);
        Monster::addName($builder, $names['Orc']);
        Monster::addInventory($builder, $inv);
        Monster::addColor($builder, Color::Red);
        Monster::addWeapons($builder, $weapons);
        Monster::addEquippedType($builder, Equipment::Weapon);
        Monster::addEquipped($builder, $sword);
        $orc = Monster::endMonster($builder);

        $builder->finish($orc);

        return $orc;
    }
}
