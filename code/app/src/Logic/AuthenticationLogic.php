<?php


namespace Gustav\App\Logic;

/**
 * // ユーザ認証シーケンス
//
// Client                              <-> Server
// Authentication (user_id)             ->
//                                         ランダム文字列を生成 (random_bytes)
//                                         秘密鍵で暗号化
//                                     <-  Authentication (user_id, secret)
// 公開鍵で復号化
// 秘密鍵で暗号化
// Authentication (user_id, secret)     ->
//                                         公開鍵で復号化
//                                         ランダム文字列と比較
//                                         アクセス用トークン生成 (access_token)
//                                     <-  Authentication (user_id, access_token)
// 以後、access_tokenを使う

 */

use Gustav\App\AppRedisKeys;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Model\AuthenticationModel;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\GustavException;

/**
 * Class AuthenticationLogic
 * @package Gustav\App\Logic
 */
class AuthenticationLogic
{
    const AUTHENTICATION_TIMEOUT = 300; // requestからpublishまでに許された時間

    /**
     * @param AuthenticationModel $request
     * @param MySQLInterface $mysql
     * @param RedisInterface $redis
     * @return AuthenticationModel
     * @throws GustavException
     * @used-by AppContainerBuilder::getDefinitions()
     */
    public function request(
        AuthenticationModel $request,
        MySQLInterface      $mysql,
        RedisInterface      $redis
    ): AuthenticationModel
    {
        // ユーザID
        $userId = $request->getUserId();

        // ランダム文字列の作成
        $randomBytes = openssl_random_pseudo_bytes(16);

        // MySQLのmaster dbへの接続adapter
        $dbAdapter = MySQLAdapter::wrap($mysql, false);

        // 鍵の取得
        list($privateKey, $publicKey) = KeyPairTable::select($dbAdapter, $userId);

        // 秘密鍵で暗号化
        openssl_private_encrypt($randomBytes, $crypted, $privateKey, OPENSSL_PKCS1_PADDING);

        // ランダム文字列をRedisに保存
        $redisAdapter = RedisAdapter::wrap($redis);
        $redisAdapter->setex(
            AppRedisKeys::idKey(AppRedisKeys::PREFIX_SECRET_TOKEN, $userId),
        self::AUTHENTICATION_TIMEOUT,
            $randomBytes
        );

        // 戻り値
        return new AuthenticationModel([
            AuthenticationModel::USER_ID => $userId,
            AuthenticationModel::SECRET => $crypted
        ]);
    }

    /**
     * @param AuthenticationModel $request
     * @param MySQLInterface $mysql
     * @param RedisInterface $redis
     * @return AuthenticationModel
     * @throws GustavException
     * @used-by AppContainerBuilder::getDefinitions()
     */
    public function publish(
        AuthenticationModel $request,
        MySQLInterface      $mysql,
        RedisInterface      $redis
    ): AuthenticationModel
    {
        $userId = $request->getUserId();
        $secret = $request->getSecret();  // 端末側の秘密鍵でencryptした鍵
    }
}