<?php


namespace Gustav\Common\Model\FlatBuffers;

use Google\FlatBuffers\FlatbufferBuilder;
use MyGame\Sample\Weapon;

class WeaponModel implements ModelSerializable
{
    public $name;
    public $damage;

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
    public function serialize(FlatbufferBuilder &$builder): int
    {
        $name = $builder->createString($this->name);
        return Weapon::createWeapon($builder, $name, $this->damage);
    }
}