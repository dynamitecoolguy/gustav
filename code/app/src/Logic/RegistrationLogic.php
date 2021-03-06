<?php


namespace Gustav\App\Logic;

use Gustav\App\AppContainerBuilder;
use Gustav\App\Database\IdentificationTable;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Operation\UserIdConverter;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Network\KeyOperatorInterface;

/**
 * ユーザ登録処理
 *
 *  Client                         <-> Server
 *
 *  Registration(note)              ->
 *                                     ユーザID, 公開用ID, 秘密鍵, 公開鍵を生成
 *                                 <-  Registration(user_id, open_id, transfer_code, note, public_key)
 *
 * Class RegistrationLogic
 * @package Gustav\App\Logic
 */
class RegistrationLogic
{
    const REGISTER_ACTION = 'REGR';

    /**
     * PackType:
     *   REG
     * 入力:
     *   RegistrationModel(note)
     * 出力:
     *   RegistrationModel(userId, openId, note, privateKey, publicKey)
     * アクセステーブル:
     *   Identification(W), KeyPair(W)
     *
     * @param RegistrationModel      $request           入力モデル
     * @param MySQLInterface         $mysql             DB
     * @param KeyOperatorInterface   $keyOperator       秘密鍵と公開鍵生成
     * @param RedisInterface         $redis             OpenIdConverterに必要
     * @return RegistrationModel                        出力モデル
     * @throws GustavException
     * @used-by AppContainerBuilder::getDefinitions()
     */
    public function register(
        RegistrationModel $request,
        MySQLInterface $mysql,
        KeyOperatorInterface $keyOperator,
        RedisInterface $redis
    ): RegistrationModel
    {
        // 登録時の備考 (登録するが未使用)
        $note = $request->getNote();

        // 秘密鍵と公開鍵の生成
        list($privateKey, $publicKey) = $keyOperator->createKeys();

        // MySQLのmaster dbへの接続adapter
        $adapter = MySQLAdapter::wrap($mysql, true);

        // DBのtransaction処理
        list($userId, $openId, $transferCode) = $adapter->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($note, $redis, $privateKey, $publicKey) {
                // ユーザID登録
                $userId = IdentificationTable::insert($adapter, $note);

                // 公開IDの計算と更新
                $openId = UserIdConverter::userIdToOpenId($redis, $userId);
                $transferCode = UserIdConverter::userIdToTransferCode($redis, $userId);
                IdentificationTable::update($adapter, $userId, $openId, $transferCode);

                // 秘密鍵と公開鍵の登録
                KeyPairTable::insert($adapter, $userId, $privateKey, $publicKey);

                return [$userId, $openId, $transferCode];
            }
        );

        // 登録結果
        return new RegistrationModel([
            RegistrationModel::USER_ID => $userId,
            RegistrationModel::OPEN_ID => $openId,
            RegistrationModel::TRANSFER_CODE => $transferCode,
            RegistrationModel::NOTE => $note,
            RegistrationModel::PUBLIC_KEY => $publicKey
        ]);
    }
}