<?php


namespace Gustav\App;

/**
 * App用のRedisのキー管理用クラス
 * Class RedisKeys
 * @package Gustav\App\Operation
 */
class RedisKeys
{
    // 処理中のユーザーID→公開ID変換の値 (@see OpenIdConverter)
    const KEY_OPEN_ID = 'openid';
}
