<?php


namespace Gustav\App\Database;


use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

/**
 * Class KeyPairTable
 * @package Gustav\App\Database
 */
class KeyPairTable
{
    /**
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
            [':uid' => $userId, ':pri' => $privateKey, ':pub' => $publicKey]
        );
    }
}