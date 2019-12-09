<?php


namespace Gustav\Common\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use MyGame\Sample\Color;
use MyGame\Sample\Equipment;
use MyGame\Sample\Monster;
use MyGame\Sample\Vec3;
use MyGame\Sample\Weapon;

class MonsterModel implements FlatBuffersSerializable, PrimitiveSerializable, ModelInterface
{
    // ひとまず名前とHPだけ

    public $name;
    public $hp;

    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return int
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
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
        $swordPos = $sword->serializeFlatBuffers($builder);
        $axePos = $axe->serializeFlatBuffers($builder);

        $treasure = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inv = Monster::createInventoryVector($builder, $treasure);

        $weapons = Monster::createWeaponsVector($builder, [$swordPos, $axePos]);

        $pos = Vec3::createVec3($builder, 1.0, 2.0, 3.0);

        // Monsterの登録
        Monster::startMonster($builder);
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
     * @return FlatBuffersSerializable
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
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

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        $sword = new WeaponModel();
        $sword->name = 'Sword';
        $sword->damage = 3;

        $axe = new WeaponModel();
        $axe->name = 'Axe';
        $axe->damage = 5;

        return [
            [1.0, 2.0, 3.0],
            $this->hp,
            $this->name,
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [$sword->serializePrimitive(), $axe->serializePrimitive()]
        ];
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        list ($pos, $hp, $name, $inv, $weapons) = $primitives;

        $self = new MonsterModel();
        $self->hp = $hp;
        $self->name = $name;

        $sword = WeaponModel::deserializePrimitive($version, $weapons[0]);
        $axe =  WeaponModel::deserializePrimitive($version, $weapons[1]);

        return $self;
    }
}