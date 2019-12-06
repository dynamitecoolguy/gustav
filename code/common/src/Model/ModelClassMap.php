<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

class ModelClassMap
{
    /**
     * 識別子 -> ModelInterfaceのクラス名
     * @var string[]
     */
    private static $chunkIdToModel = [];

    /**
     * ModelInterfaceのクラス名 -> 識別子
     * @var string[]
     */
    private static $modelToChunkId = [];

    /**
     * モデルのクラスを登録する
     * @param string $chunkId
     * @param string $model ModelInterfaceを実装したクラスのクラス名
     * @throws ModelException
     */
    public static function registerModel(string $chunkId, string $model): void
    {
        // 重複チェック (同じクラス名の場合は無視する)
        if (isset(self::$chunkIdToModel[$chunkId])
            && self::$chunkIdToModel[$chunkId] !== $model)
        {
            $anotherObjectClass = self::$chunkIdToModel[$chunkId];
            throw new ModelException("${model}'s chunkId is already used by ${anotherObjectClass}");
        }

        self::$chunkIdToModel[$chunkId] = $model;
        self::$modelToChunkId[$model] = $chunkId;
    }

    /**
     * 識別コードに対応するクラスを返す。なければModelException
     * @param string $chunkId 識別コード
     * @return string クラス名
     * @throws ModelException
     */
    public static function findModelClass(string $chunkId): string
    {
        if (!isset(self::$chunkIdToModel[$chunkId])) {
            throw new ModelException("Not found for chunkId(${chunkId})");
        }
        return self::$chunkIdToModel[$chunkId];
    }

    /**
     * クラスに対応する識別コードを返す
     * @param string $model モデルのクラス名
     * @return string 識別コード
     * @throws ModelException
     */
    public static function findChunkId(string $model): string
    {
        if (!isset(self::$modelToChunkId[$model])) {
            throw new ModelException("Not found for model(${model})");
        }
        return self::$modelToChunkId[$model];
    }

    /**
     * マップのリセット
     */
    public static function resetMap(): void
    {
        self::$modelToChunkId = [];
        self::$chunkIdToModel = [];
    }
}
