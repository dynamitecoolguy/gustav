<?php


namespace Gustav\Common\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use MyGame\Sample\Color;
use MyGame\Sample\Equipment;
use MyGame\Sample\Monster;
use MyGame\Sample\Vec3;
use MyGame\Sample\Weapon;

class MonsterModel implements ModelInterface
{
    // ひとまず名前とHPだけ

    public $name;
    public $hp;

    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return int
     */
    public function serialize(FlatbufferBuilder &$builder): int
    {
        // 名前登録
        $name = $builder->createString($this->name);

        $sword = new WeaponModel();
        $sword->name = 'Sword';
        $sword->damage = 3;

        $axe = new WeaponModel();
        $axe->name = 'Axe';
        $axe->damage = 5;

        // Monsterで使用されているobject, vectorなどを登録
        $swordPos = $sword->serialize($builder);
        $axePos = $axe->serialize($builder);

        $treasure = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inv = Monster::createInventoryVector($builder, $treasure);

        $weapons = Monster::createWeaponsVector($builder, [$swordPos, $axePos]);

        $pos = Vec3::createVec3($builder, 1.0, 2.0, 3.0);

        // Monsterの登録
        Monster::startMonster($builder);;
        Monster::addPos($builder, $pos);
        Monster::addHp($builder, $this->hp);
        Monster::addName($builder, $name);
        Monster::addInventory($builder, $inv);
        Monster::addColor($builder, Color::Red);
        Monster::addWeapons($builder, $weapons);
        Monster::addEquippedType($builder, Equipment::Weapon);
        Monster::addEquipped($builder, $swordPos);
        return Monster::endMonster($builder);
    }

    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return ModelInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface
    {
        $monster = Monster::getRootAsMonster($buffer);

        $self = new MonsterModel();
        $self->name = $monster->getName();
        $self->hp = $monster->getHp();

        $weapon = $monster->getEquipped(new Weapon());
        $equipped = WeaponModel::convertFromTable($weapon);
        $equipped->name = $weapon->getName();
        $equipped->damage = $weapon->getDamage();

        return $self;
    }
}