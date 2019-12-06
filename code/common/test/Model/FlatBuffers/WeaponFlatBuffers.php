<?php


namespace Gustav\Common\Model\FlatBuffers;

use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\ModelInterface;
use MyGame\Sample\Weapon;

class WeaponFlatBuffers implements FlatBuffersSerializable, ModelInterface
{
    public $name;
    public $damage;

    /**
     * @param Weapon $weapon
     * @return WeaponFlatBuffers
     */
    public static function convertFromTable(Weapon $weapon): WeaponFlatBuffers
    {
        $self = new WeaponFlatBuffers();
        $self->name = $weapon->getName();
        $self->damage = $weapon->getDamage();

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function serialize(FlatbufferBuilder &$builder): int
    {
        $name = $builder->createString($this->name);
        return Weapon::createWeapon($builder, $name, $this->damage);
    }
}