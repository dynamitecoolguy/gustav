<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

class ModelClassMap
{
    /**
     * @var ?string[]
     */
    private static $chunkIdToModelClassMap = null;

    /**
     * @var array
     */
    protected static $initialClassMap = [
        // Hogehoge::chunkId() => Hogehoge::class
    ];

    /**
     * モデルのクラスを登録する
     * @param string $objectClass
     * @throws ModelException
     */
    public static function registerModel(string $objectClass): void
    {
        static::checkDefault();
        if (!is_subclass_of($objectClass, ModelInterface::class)) {
            throw new ModelException("${objectClass} is not subclass of ModelInterface");
        }

        $chunkId = call_user_func([$objectClass, 'chunkId']);

        if (isset(self::$chunkIdToModelClassMap[$chunkId])
            && self::$chunkIdToModelClassMap[$chunkId] !== $objectClass)
        {
            $anotherObjectClass = self::$chunkIdToModelClassMap[$chunkId];
            throw new ModelException("${objectClass}'s chunkId is already used by ${anotherObjectClass}");
        }

        self::$chunkIdToModelClassMap[$chunkId] = $objectClass;
    }

    /**
     * 識別コードに対応するクラスを返す。なければnull
     * @param $chunkId
     * @return string
     * @throws ModelException
     */
    public static function findModelClass($chunkId): string
    {
        static::checkDefault();
        if (isset(self::$chunkIdToModelClassMap[$chunkId])) {
            return self::$chunkIdToModelClassMap[$chunkId];
        }
        throw new ModelException("Not found for chunkId(${chunkId})");
    }

    /**
     * chunkIdToModelClassMapにinitialClassMapがセットされていなければセットする
     */
    protected static function checkDefault()
    {
        if (is_null(self::$chunkIdToModelClassMap)) {
            self::$chunkIdToModelClassMap = self::$initialClassMap;
        }
    }
}
