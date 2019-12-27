<?php


namespace Gustav\App\Logic;

use Gustav\App\Database\TransferCodeTable;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Exception\DuplicateEntryException;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Exception\ModelException;

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
     * @param int $userId
     * @param MySQLInterface $mysql
     * @return TransferCodeModel
     * @throws GustavException
     */
    public function setPassword(
        int               $userId,
        MySQLInterface    $mysql
    ): TransferCodeModel
    {
        // MySQLのslave dbへの接続adapter
        $adapter = MySQLAdapter::wrap($mysql, false);

        // TODO:

        return new TransferCodeModel();
    }

    /**
     * @return RegistrationModel
     * @throws GustavException
     */
    public function execute(
    ): RegistrationModel
    {
        return new RegistrationModel();
    }
}