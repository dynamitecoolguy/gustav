<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

class ModelMapper
{
    /**
     * PackType -> ModelInterfaceのクラス名
     * @var string[]
     */
    private static $packTypeToModelClass = [];

    /**
     * ModelInterfaceのクラス名 -> PackType
     * @var string[]
     */
    private static $modelClassToPackType = [];

    /**
     * モデルのクラスを登録する
     * @param string $packType
     * @param string $modelClass  ModelInterfaceを実装したクラスのクラス名
     * @throws ModelException
     */
    public static function registerModel(string $packType, string $modelClass): void
    {
        // 重複チェック (同じクラス名の場合は無視する)
        if (isset(self::$packTypeToModelClass[$packType])
            && self::$packTypeToModelClass[$packType] !== $modelClass)
        {
            $anotherObjectClass = self::$packTypeToModelClass[$packType];
            throw new ModelException("${modelClass}'s packType is already used by ${anotherObjectClass}");
        }

        self::$packTypeToModelClass[$packType] = $modelClass;
        self::$modelClassToPackType[$modelClass] = $packType;
    }

    /**
     * 識別コードに対応するクラスを返す。なければModelException
     * @param string $packType 識別コード
     * @return string クラス名
     * @throws ModelException
     */
    public static function findModelClass(string $packType): string
    {
        if (!isset(self::$packTypeToModelClass[$packType])) {
            throw new ModelException("Not found for packType(${packType})");
        }
        return self::$packTypeToModelClass[$packType];
    }

    /**
     * クラスに対応する識別コードを返す
     * @param string $modelClass モデルのクラス名
     * @return string 識別コード
     * @throws ModelException
     */
    public static function findPackType(string $modelClass): string
    {
        if (!isset(self::$modelClassToPackType[$modelClass])) {
            throw new ModelException("Not found for model(${modelClass})");
        }
        return self::$modelClassToPackType[$modelClass];
    }

    /**
     * マップのリセット
     */
    public static function resetMap(): void
    {
        self::$modelClassToPackType = [];
        self::$packTypeToModelClass = [];
    }
}
