<?php


namespace Gustav\App\Logic;

use Gustav\App\AppContainerBuilder;
use Gustav\App\Database\IdentificationTable;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Database\TransferCodeTable;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\ResultModel;
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
 *                                       <-  Result (result)
 *
 * [引き継ぎ実施](TRCE)
 * TransferCode (password, transferCode)  ->
 *                                           ユーザID, 公開用IDを取得
 *                                           秘密鍵, 公開鍵を再発行
 *                                       <-  Registration(user_id, open_id, transfer_code, note, public_key)
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
     * @return ResultModel
     * @throws GustavException
     * @used-by AppContainerBuilder::getDispatcherTable()
     */
    public function setPassword(
        TransferCodeModel $request,
        int               $userId,
        MySQLInterface    $mysql
    ): ResultModel
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

        return new ResultModel([
            ResultModel::RESULT => 0
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
        $inputPasswordHash = hash('sha256', $password);

        list($userId, $openId, $note) = IdentificationTable::selectFromCode($adapter, $transferCode);
        if (is_null($userId)) {
            // 引き継ぎコードが見つからない
            return new RegistrationModel([
                RegistrationModel::TRANSFER_CODE => $transferCode,
            ]);
        }

        list($registeredPasswordHash) = TransferCodeTable::select($adapter, $userId);
        if ($inputPasswordHash !== $registeredPasswordHash) {
            // パスワードが一致しない
            return new RegistrationModel([
                RegistrationModel::TRANSFER_CODE => $transferCode,
            ]);
        }

        // 秘密鍵と公開鍵の生成
        list($privateKey, $publicKey) = $keyOperator->createKeys();

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