<?php


namespace Gustav\Common\Adapter;

use PDO;

/**
 * Interface PgSQLInterface
 * @package Gustav\Common\Adapter
 */
interface PgSQLInterface
{
    /**
     * @return PDO
     */
    public function getPDO(): PDO;
}