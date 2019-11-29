<?php


namespace Gustav\Common\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use MyGame\Sample\Color as FBColor;
use MyGame\Sample\Equipment as FBEquipment;
use MyGame\Sample\Monster as FBMonster;
use MyGame\Sample\Vec3 as FBVec3;
use MyGame\Sample\Weapon as FBWeapon;

class Monster implements ModelInterface
{
    // ひとまず名前とHPだけ

    public $name;
    public $hp;

    /**
     * 識別コードの取得
     * @return string
     */
    public static function chunkId(): string
    {
        return 'MON';
    }

    /**
     * 現在のフォーマットバージョンを返す (1..255)
     * @return int
     */
    public static function formatVersion(): int
    {
        return 1;
    }

    /**
     * シリアル化
     * @param FlatbufferBuilder $builder
     * @return void
     */
    public function serialize(FlatbufferBuilder &$builder): void
    {
        // 名前登録
        $names = [
            'Sword' => $builder->createString('Sword'),
            'Axe' => $builder->createString('Axe'),
            $this->name => $builder->createString($this->name)
        ];

        // Monsterで使用されているobject, vectorなどを登録
        $sword = FBWeapon::createWeapon($builder, $names['Sword'], 3);
        $axe = FBWeapon::createWeapon($builder, $names['Axe'], 5);

        $treasure = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $inv = FBMonster::createInventoryVector($builder, $treasure);

        $weapons = FBMonster::createWeaponsVector($builder, [$sword, $axe]);

        $pos = FBVec3::createVec3($builder, 1.0, 2.0, 3.0);

        // Monsterの登録
        FBMonster::startMonster($builder);;
        FBMonster::addPos($builder, $pos);
        FBMonster::addHp($builder, $this->hp);
        FBMonster::addName($builder, $names[$this->name]);
        FBMonster::addInventory($builder, $inv);
        FBMonster::addColor($builder, FBColor::Red);
        FBMonster::addWeapons($builder, $weapons);
        FBMonster::addEquippedType($builder, FBEquipment::Weapon);
        FBMonster::addEquipped($builder, $sword);
        $orc = FBMonster::endMonster($builder);

        // 登録完了
        $builder->finish($orc);
    }

    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return ModelInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface
    {
        $monster = FBMonster::getRootAsMonster($buffer);

        $self = new Monster();
        $self->name = $monster->getName();
        $self->hp = $monster->getHp();

        return $self;
    }
}