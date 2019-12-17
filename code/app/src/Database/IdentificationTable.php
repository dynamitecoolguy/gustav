<?php


namespace Gustav\App\Database;


use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;

class IdentificationTable
{
    /**
     * @param MySQLAdapter $adapter
     * @param string $note
     * @return int
     * @throws DatabaseException
     */
    public static function insert(MySQLAdapter $adapter, string $note): int
    {
        $adapter->execute(
            'insert into identification(open_id, note) values(0, :note)',
            [':note' => $note]
        );
        return (int)$adapter->lastInsertId();
    }

    /**
     * @param MySQLAdapter $adapter
     * @param int $userId
     * @param string $openId
     * @throws DatabaseException
     */
    public static function updateOpenId(MySQLAdapter $adapter, int $userId, string $openId): void
    {
        $adapter->execute(
            'update identification set open_id=:oid where user_id=:uid',
            [':oid' => $openId, ':uid' => $userId]
        );

    }
}
