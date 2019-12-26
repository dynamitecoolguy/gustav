<?php


namespace Gustav\App\Logic;

use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Model\ModelInterface;
use Psr\Container\ContainerInterface;

/**
 * データ移管シーケンス
 *
 * Client                              <-> Server
 *
 * [引き継ぎコード取得](TRCG)
 * TransferCode                            ->
 *                                            引き継ぎコード取得
 *                                        <-  TransferCode (transferCode)
 *
 * [引き継ぎパスワード設定](TRCP)
 * TransferCode (password)                ->
 *                                           パスワードハッシュを登録
 *                                       <-  TransferCode (transferCode, result)
 *
 * [引き継ぎコード再発行](TRCR)
 * TransferCode                           ->
 *                                           引き継ごコード再発行し、パスワードを無効化
 *                                       <-  TransferCode (transferCode)
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
    const GET_ACTION          = 'TRCG';
    const SET_PASSWORD_ACTION = 'TRCP';
    const RESET_ACTION        = 'TRCR';
    const EXECUTE_ACTION      = 'TRCE';

    private static $chars = '0123456789abcdefghjklmnpqrstuvwxyz'; // length:34

    public function get(
        int               $userId,
        MySQLInterface    $mysql
    ): TransferCodeModel
    {
        return new TransferCodeModel();
    }

    /**
     * 8桁34進数文字列の生成
     * @return string
     */
    private static function createTransferCode(): string
    {
        $c = '';
        for ($i = 0; $i < 8; $i++) {
            $c .= self::$chars[mt_rand(0, 33)];
        }
        return $c;
    }
}