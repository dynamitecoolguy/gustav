<?php


namespace Gustav\App\Database;


use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * MySQL key_pairテーブルの操作
 * create table key_pair (
 *   user_id int unsigned not null,                             -- ユーザID
 *   private_key varbinary(2048) not null,                      -- RSA秘密鍵
 *   public_key varbinary(1024) not null,                       -- RSA公開鍵
 *   created_at timestamp default current_timestamp not null,
 *   primary key(user_id),
 *   foreign key(user_id) references identification (user_id)
 * );
 * Class KeyPairTable
 * @package Gustav\App\Database
 */
class KeyPairTable
{
    /**
     * 鍵の登録
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $privateKey
     * @param string $publicKey
     * @throws DatabaseException
     */
    public static function insert(MySQLAdapter $adapter, int $userId, string $privateKey, string $publicKey): void
    {
        $adapter->execute(
            'insert into key_pair(user_id, private_key, public_key) values(:uid, :pri, :pub)',
            ['uid' => $userId, 'pri' => $privateKey, 'pub' => $publicKey]
        );
    }

    /**
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @return array|null
     * @throws DatabaseException
     */
    public static function select(MySQLAdapter $adapter, int $userId): ?array
    {
        return $adapter->cachedFetch(
            static::key($userId),
            'select private_key, public_key from key_pair where user_id=:uid',
            ['uid' => $userId]
        );
    }

    /**
     * @param int $userId
     * @return string
     */
    protected static function key(int $userId): string
    {
        return AppRedisKeys::idKey(AppRedisKeys::PREFIX_KEY_PAIR, $userId);
    }
}