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

    // Identification Table
    const PREFIX_IDENTIFICATION = 'id_';

    // KeyPair Table
    const PREFIX_KEY_PAIR = 'kp_';

    // Authenticationの秘密トークン
    const PREFIX_SECRET_TOKEN = 'st_';
}
