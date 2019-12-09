<?php


namespace Gustav\Common\Model;

use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use MyGame\Sample\Weapon;

class WeaponModel implements FlatBuffersSerializable, PrimitiveSerializable, ModelInterface
{
    public $name;
    public $damage;

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $name = $builder->createString($this->name);
        return Weapon::createWeapon($builder, $name, $this->damage);
    }

    /**
     * @param Weapon $weapon
     * @return WeaponModel
     */
    public static function convertFromTable(Weapon $weapon): WeaponModel
    {
        $self = new WeaponModel();
        $self->name = $weapon->getName();
        $self->damage = $weapon->getDamage();

        return $self;
    }

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        return self::convertFromTable(Weapon::getRootAsWeapon($buffer));
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->name, $this->damage];
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        $self = new WeaponModel();
        list($self->name, $self->damage) = $primitives;

        return $self;
    }
}
