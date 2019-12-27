<?php


namespace Gustav\App\Database;

use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * MySQL transfer_codeテーブルの操作
 *
 * create table transfer_code (
 *   user_id int unsigned not null,                             -- ユーザID
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
     * コードの登録
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $passwordHash
     * @throws DatabaseException
     */
    public static function insert(MySQLAdapter $adapter, int $userId, string $passwordHash): void
    {
        $adapter->execute(
            'insert into transfer_code(user_id, password_hash) values(:uid, :ph)',
            ['uid' => $userId, 'ph' => $passwordHash]
        );
    }

    /**
     * コードの取得
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @return array|null
     * @throws DatabaseException
     */
    public static function select(MySQLAdapter $adapter, int $userId): ?array
    {
        return $adapter->cachedFetch(
            static::key($userId),
            'select user_id, password_hash from transfer_code where user_id=:uid',
            ['uid' => $userId]
        );
    }

    /**
     * @param int $userId
     * @return string
     */
    protected static function key(int $userId): string
    {
        return AppRedisKeys::idKey(AppRedisKeys::PREFIX_TRANSFER_CODE, $userId);
    }
}