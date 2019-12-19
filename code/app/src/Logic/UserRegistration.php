<?php


namespace Gustav\App\Logic;

use Gustav\App\AppContainerBuilder;
use Gustav\App\Database\IdentificationTable;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Model\IdentificationModel;
use Gustav\App\Operation\OpenIdConverterInterface;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLMasterInterface;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Network\KeyOperatorInterface;

/**
 * ユーザ登録処理
 * Class UserRegistration
 * @package Gustav\App\Logic
 */
class UserRegistration
{
    /**
     * 入力:
     *   IdentificationModel(note)
     * 出力:
     *   IdentificationModel(userId, openId, note, privateKey, publicKey)
     * アクセステーブル:
     *   Identification, KeyPair
     *
     * @param IdentificationModel      $request           入力モデル
     * @param MySQLMasterInterface     $mysql             DB
     * @param KeyOperatorInterface     $keyOperator       秘密鍵と公開鍵生成
     * @param OpenIdConverterInterface $openIdConverter   公開ID生成
     * @param RedisInterface           $redis             OpenIdConverterに必要
     * @return IdentificationModel                        出力モデル
     * @throws GustavException
     * @used-by AppContainerBuilder::getDefinitions()
     */
    public function register(
        IdentificationModel $request,
        MySQLMasterInterface $mysql,
        KeyOperatorInterface $keyOperator,
        OpenIdConverterInterface $openIdConverter,
        RedisInterface $redis): IdentificationModel
    {
        // 登録時の備考 (登録するが未使用)
        $note = $request->getNote();

        // 秘密鍵と公開鍵の生成
        list($privateKey, $publicKey) = $keyOperator->createKeys();

        // MySQLのmaster dbへの接続adapter
        $adapter = MySQLAdapter::wrap($mysql, true);

        // DBのtransaction処理
        list($userId, $openId) = $adapter->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($note, $openIdConverter, $redis, $privateKey, $publicKey) {
                // ユーザID登録
                $userId = IdentificationTable::insert($adapter, $note);

                // 公開IDの計算と更新
                $openId = $openIdConverter->userIdToOpenId($redis, $userId);
                IdentificationTable::updateOpenId($adapter, $userId, $openId);

                // 秘密鍵と公開鍵の登録
                KeyPairTable::insert($adapter, $userId, $privateKey, $publicKey);

                return [$userId, $openId];
            }
        );

        // 登録結果
        return new IdentificationModel([
            IdentificationModel::USER_ID => $userId,
            IdentificationModel::OPEN_ID => $openId,
            IdentificationModel::NOTE => $note,
            IdentificationModel::PRIVATE_KEY => $privateKey,
            IdentificationModel::PUBLIC_KEY => $publicKey
        ]);
    }
}