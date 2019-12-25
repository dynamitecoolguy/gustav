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
            throw new ModelException(
                "${modelClass}'s packType is already used by ${anotherObjectClass}",
                ModelException::PACK_TYPE_IS_DUPLICATED
            );
        }

        self::$packTypeToModelClass[$packType] = $modelClass;
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
            throw new ModelException(
                "Not found for packType(${packType})",
                ModelException::PACK_TYPE_IS_UNREGISTERED
            );
        }
        return self::$packTypeToModelClass[$packType];
    }

    /**
     * マップのリセット
     */
    public static function resetMap(): void
    {
        self::$packTypeToModelClass = [];
    }
}
