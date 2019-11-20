<?php


namespace Gustav\Common\Adapter;

use PDO;

/**
 * Class MySQLAdapter
 * @package Gustav\Common\Adapter
 */
class MySQLAdapter implements MySQLInterface
{
    /**
     * @var PDO PDO Object
     */
    private $pdo;

    /**
     * MySQLAdapter constructor.
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
