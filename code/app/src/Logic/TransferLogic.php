<?php


namespace Gustav\App\Logic;

use Gustav\App\AppContainerBuilder;
use Gustav\App\Database\IdentificationTable;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Database\TransferCodeTable;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Network\KeyOperatorInterface;

/**
 * データ移管シーケンス
 *
 * Client                              <-> Server
 *
 * [引き継ぎパスワード設定](TRCP)
 * TransferCode (password)                ->
 *                                           パスワードハッシュを登録
 *                                       <-  TransferCode (transferCode, result)
 *
 * [引き継ぎ実施](TRCE)
 * TransferCode (password, transferCode)  ->
 *                                           ユーザID, 公開用IDを取得
 *                                           秘密鍵, 公開鍵を再発行
 *                                       <-  Registration(user_id, open_id, note, public_key)
 *
 * Class TransferLogic
 * @package Gustav\App\Logic
 */
class TransferLogic
{
    const SET_PASSWORD_ACTION = 'TRCP';
    const EXECUTE_ACTION      = 'TRCE';

    /**
     * パスワードのセット
     * @param TransferCodeModel $request
     * @param int $userId
     * @param MySQLInterface $mysql
     * @return TransferCodeModel
     * @throws GustavException
     * @used-by AppContainerBuilder::getDispatcherTable()
     */
    public function setPassword(
        TransferCodeModel $request,
        int               $userId,
        MySQLInterface    $mysql
    ): TransferCodeModel
    {
        $password = $request->getPassword();
        $passwordHash = hash('sha256', $password);

        // MySQLのmaster dbへの接続adapter
        $adapter = MySQLAdapter::wrap($mysql, true);

        // DBのtransaction処理
        $adapter->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($userId, $passwordHash) {
                // パスワードハッシュ登録
                TransferCodeTable::insert($adapter, $userId, $passwordHash);
            }
        );

        return new TransferCodeModel([
            TransferCodeModel::PASSWORD => $passwordHash
        ]);
    }

    /**
     * @param TransferCodeModel $request
     * @param MySQLInterface $mysql
     * @param KeyOperatorInterface $keyOperator
     * @return RegistrationModel
     * @throws GustavException
     * @used-by AppContainerBuilder::getDispatcherTable()
     */
    public function execute(
        TransferCodeModel    $request,
        MySQLInterface       $mysql,
        KeyOperatorInterface $keyOperator
    ): RegistrationModel
    {
        // MySQLのmaster dbへの接続adapter
        $adapter = MySQLAdapter::wrap($mysql, false);

        $transferCode = $request->getTransferCode();
        $password = $request->getPassword();
        $passwordHash = hash('sha256', $password);

        list($userId, $storedHash) = TransferCodeTable::selectFromCode($adapter, $transferCode);

        if (is_null($userId) || $passwordHash != $storedHash) {
            // パスワードが違う
            return new RegistrationModel([
                RegistrationModel::TRANSFER_CODE => $transferCode,
            ]);
        }

        // 秘密鍵と公開鍵の生成
        list($privateKey, $publicKey) = $keyOperator->createKeys();

        // 公開IDの取得
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($openId, $_, $note) = IdentificationTable::select($adapter, $userId);
        if (is_null($openId)) {
            // ありえないはず
            // TODO: Logging
            return new RegistrationModel([
                RegistrationModel::TRANSFER_CODE => $transferCode,
            ]);
        }

        // MasterDBに切り替え
        $adapter->setMasterMode();

        // DBのtransaction処理
        $adapter->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($userId, $privateKey, $publicKey) {
                // 秘密鍵と公開鍵の登録
                KeyPairTable::update($adapter, $userId, $privateKey, $publicKey);
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