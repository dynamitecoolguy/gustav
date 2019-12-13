<?php


namespace Gustav\Common\Adapter;

use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;
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
     * @param ApplicationConfigInterface $config
     * @throws ConfigException
     */
    public function __construct(ApplicationConfigInterface $config)
    {
        list($host, $port) = NameResolver::resolveHostAndPort($config->getValue('pgsql', 'host'));
        $dsn = 'pgsql:host=' . $host . ';dbname=' . $config->getValue('pgsql', 'dbname');
        if ($port > 0) {
            $dsn .= ';port=' . $port;
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        $this->pdo = new PDO(
            $dsn,
            $config->getValue('pgsql', 'user'),
            $config->getValue('pgsql', 'password'),
            $options
        );
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }
}