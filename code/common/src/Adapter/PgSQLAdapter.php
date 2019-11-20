<?php


namespace Gustav\Common\Adapter;

use PDO;

/**
 * Class PgSQLAdapter
 * @package Gustav\Common\Adapter
 */
class PgSQLAdapter implements PgSQLInterface
{
    /**
     * @var PDO PDO Object
     */
    private $pdo;

    /**
     * PgSQLAdapter constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}