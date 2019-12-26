<?php


namespace Gustav\App\Database;


use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * MySQL Identificationテーブルの制御
 * create table identification (
 *   user_id int unsigned not null auto_increment,              -- ユーザID
 *   open_id binary(10) not null,                               -- 公開ID(10桁)
 *   note varchar(256) not null,                                -- 登録時のnote
 *   created_at timestamp default current_timestamp not null,
 *   primary key(user_id),
 *   unique index (open_id)
 * );
 * Class IdentificationTable
 * @package Gustav\App\Database
 */
class IdentificationTable
{
    /**
     * レコードの追加
     * @param MySQLAdapter $adapter
     * @param string $note
     * @return int
     * @throws DatabaseException
     */
    public static function insert(MySQLAdapter $adapter, string $note): int
    {
        $adapter->execute(
            'insert into identification(open_id, note) values(null, :note)',
            ['note' => $note]
        );
        return (int)$adapter->lastInsertId();
    }

    /**
     * 公開IDの更新
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $openId
     * @throws DatabaseException
     */
    public static function updateOpenId(MySQLAdapter $adapter, int $userId, string $openId): void
    {
        $adapter->execute(
            'update identification set open_id=:oid where user_id=:uid',
            ['oid' => $openId, 'uid' => $userId]
        );
        $adapter->invalidateKey(self::key($userId));
    }

    /**
     * 指定されたユーザの公開ID, note, 作成日(unix time)を返す
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @return array
     * @throws DatabaseException
     */
    public static function select(MySQLAdapter $adapter, int $userId): array
    {
        return $adapter->cachedFetch(
            self::key($userId),
            'select open_id, note, created_at from identification where user_id=:uid',
            ['uid' => $userId],
            2 // parseTimestamp('created_at')
        );
    }

    /**
     * @param int $userId
     * @return string
     */
    protected static function key(int $userId): string
    {
        return AppRedisKeys::idKey(AppRedisKeys::PREFIX_IDENTIFICATION, $userId);
    }
}
