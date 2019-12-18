<?php


namespace Gustav\App;

use Gustav\Common\CommonRedisKeys;

/**
 * App用のRedisのキー管理用クラス
 * Class AppRedisKeys
 * @package Gustav\App\Operation
 */
class AppRedisKeys extends CommonRedisKeys
{
    // 処理中のユーザーID→公開ID変換の値 (@see OpenIdConverter)
    const KEY_OPEN_ID = 'openid';
}
