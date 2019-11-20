<?php


namespace Gustav\Common\Adapter;

use PDO;

/**
 * Interface MySQLInterface
 * @package Gustav\Common\Adapter
 */
interface MySQLInterface
{
    /**
     * @return PDO
     */
    public function getPDO(): PDO;
}
