<?php


namespace Gustav\Common\Network;

/**
 * ホスト名解決クラス
 * Class NameResolver
 * @package Gustav\Common\Network
 */
class NameResolver
{
    /**
     * APCUに保存するときのPrefix
     */
    const IP_APCU_PREFIX = 'i_';

    /**
     * 名前のIPを保存する時間
     */
    const CACHE_TTL = 5;

    /**
     * ホストのIP(文字列表現)を返す
     * @param string $name
     * @return string
     */
    public static function getIp(string $name): string
    {
        $key = self::makeKey($name);
        $result = apcu_fetch($key, $success);
        if ($success) {
            return $result;
        }
        $ip = gethostbyname($name);
        apcu_store($key, $ip, self::CACHE_TTL);
        return $ip;
    }

    /**
     * 内部キャッシュをクリアします
     * @param string $name
     */
    public static function flushCache(string $name): void
    {
        $key = self::makeKey($name);
        apcu_delete($key);
    }

    /**
     * AOCUに格納するときのキーを生成する
     * @param string $name
     * @return string
     */
    private static function makeKey(string $name): string
    {
        return self::IP_APCU_PREFIX . $name;
    }
}
