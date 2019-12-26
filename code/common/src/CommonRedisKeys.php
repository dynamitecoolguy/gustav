<?php


namespace Gustav\Common;

/**
 * Redisの共通キー管理用クラス
 * Class AppRedisKeys
 * @package Gustav\Common
 */
class CommonRedisKeys
{
    const PREFIX_TOKEN = 'tk_';


    /**
     * IDを示すキーを返す
     * @param string $prefix
     * @param int $id
     * @return string
     */
    public static function idKey(string $prefix, int $id)
    {
        return $prefix . strval($id);
    }
}
