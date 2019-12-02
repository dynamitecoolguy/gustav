<?php


namespace Gustav\Common\Model;

use Google\FlatBuffers\FlatbufferBuilder;
use MyGame\Sample\Weapon as FBWeapon;

class Weapon implements ModelSerializable
{
    public $name;
    public $damage;

    /**
     * @param FBWeapon $weapon
     * @return Weapon
     */
    public static function convertFromTable(FBWeapon $weapon): Weapon
    {
        $self = new Weapon();
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
        return FBWeapon::createWeapon($builder, $name, $this->damage);
    }

}