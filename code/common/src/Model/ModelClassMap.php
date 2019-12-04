<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

class ModelClassMap
{
    /**
     * 識別子 -> ModelInterfaceのクラス名
     * @var string[]
     */
    private static $chunkIdToModelClassMap = [];

    /**
     * モデルのクラスを登録する
     * @param string $chunkId
     * @param string $objectClass ModelInterfaceを実装したクラスのクラス名
     * @throws ModelException
     */
    public static function registerModel(string $chunkId, string $objectClass): void
    {
        // 重複チェック (同じクラス名の場合は無視する)
        if (isset(self::$chunkIdToModelClassMap[$chunkId])
            && self::$chunkIdToModelClassMap[$chunkId] !== $objectClass)
        {
            $anotherObjectClass = self::$chunkIdToModelClassMap[$chunkId];
            throw new ModelException("${objectClass}'s chunkId is already used by ${anotherObjectClass}");
        }

        self::$chunkIdToModelClassMap[$chunkId] = $objectClass;
    }

    /**
     * 識別コードに対応するクラスを返す。なければModelException
     * @param string $chunkId 識別コード
     * @return string クラス名
     * @throws ModelException
     */
    public static function findModelClass(string $chunkId): string
    {
        if (!isset(self::$chunkIdToModelClassMap[$chunkId])) {
            throw new ModelException("Not found for chunkId(${chunkId})");
        }
        $objectClass = self::$chunkIdToModelClassMap[$chunkId];
        if (!is_subclass_of($objectClass, ModelInterface::class)) {
            throw new ModelException("${objectClass} is not subclass of ModelInterface");
        }

        return $objectClass;
    }
}
