<?php


namespace Gustav\App\Database;


use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * MySQL identificationテーブルの制御
 * create table identification (
 *   user_id int unsigned not null auto_increment,              -- ユーザID
 *   open_id binary(10) null default null,                      -- 公開ID(10桁)
 *   transfer_code binary(8) null default null,                 -- 移管コード(8桁)
 *   note varchar(256) not null,                                -- 登録時のnote
 *   created_at timestamp default current_timestamp not null,
 *   primary key(user_id),
 *   unique index (open_id),
 *   unique index (transfer_code)
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
            'insert into identification(note) values(:note)',
            ['note' => $note]
        );
        return (int)$adapter->lastInsertId();
    }

    /**
     * 公開IDと引き継ぎコードの更新
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $openId
     * @param string $transferCode
     * @throws DatabaseException
     */
    public static function update(MySQLAdapter $adapter, int $userId, string $openId, string $transferCode): void
    {
        $adapter->execute(
            'update identification set open_id=:oid, transfer_code=:tc where user_id=:uid',
            ['oid' => $openId, 'tc' => $transferCode, 'uid' => $userId]
        );
        $adapter->invalidateKey(self::key($userId));
    }

    /**
     * 指定されたユーザの公開ID, 移管コード, note, 作成日(unix time)を返す
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @return array|null
     * @throws DatabaseException
     */
    public static function select(MySQLAdapter $adapter, int $userId): ?array
    {
        return $adapter->cachedFetch(
            static::key($userId),
            'select open_id, transfer_code, note, created_at from identification where user_id=:uid',
            ['uid' => $userId],
            3 // parseTimestamp('created_at')
        );
    }

    /**
     * 引き継ぎコードから、ユーザID, 公開ID, note, 作成日(unix time)を取得
     * @param MySQLAdapter $adapter
     * @param string $code
     * @return array|null      (ユーザID, パスワードハッシュ)
     * @throws DatabaseException
     */
    public static function selectFromCode(MySQLAdapter $adapter, string $code): ?array
    {
        return $adapter->fetch(
            'select user_id, open_id, note, created_at from identification where transfer_code=:code',
            ['code' => $code],
            3 // parseTimestamp('created_at')
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
