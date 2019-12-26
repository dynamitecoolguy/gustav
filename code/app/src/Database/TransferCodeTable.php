<?php


namespace Gustav\App\Database;

use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * MySQL transfer_codeテーブルの操作
 *
 * create table transfer_code (
 *   user_id int unsigned not null,                             -- ユーザID
 *   transfer_code binary(8) not null,                          -- 移管コード
 *   password_hash varbinary(256) not null,                     -- パスワードのハッシュ
 *   created_at timestamp default current_timestamp not null,
 *   primary key(user_id),
 *   unique index(transfer_code),
 *   index(password_hash(16)),
 *   foreign key(user_id) references identification (user_id)
 * )
 * Class TransferCodeTable
 * @package Gustav\App\Database
 */
class TransferCodeTable
{
    /**
     * 鍵の登録
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $transferCode
     * @throws DatabaseException
     */
    public static function insert(MySQLAdapter $adapter, int $userId, string $transferCode): void
    {
        $adapter->execute(
            'insert into transfer_code(user_id, transfer_code, password_hash) values(:uid, :code, "")',
            ['uid' => $userId, 'code' => $transferCode]
        );
    }

}