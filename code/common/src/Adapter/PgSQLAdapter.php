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
     * @var string ホスト名 または ホスト名:ポート
     */
    private $host = '';

    /**
     * @var string database名
     */
    private $dbName = '';

    /**
     * @var string ユーザ名
     */
    private $user = '';

    /**
     * @var string パスワード
     */
    private $password = '';

    /**
     * @var ?PDO PDO Object
     */
    private $pdo = null;

    /**
     * @param ApplicationConfigInterface $config
     * @return PgSQLAdapter
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): PgSQLAdapter
    {
        $host = $config->getValue('pgsql', 'host');
        $dbName = $config->getValue('pgsql', 'dbname');
        $user = $config->getValue('pgsql', 'user');
        $password = $config->getValue('pgsql', 'password');

        $self = new PgSQLAdapter();
        $self->setConfig($host, $dbName, $user, $password);
        return $self;
    }

    /**
     * PgSQLInterfaceをPgSQLAdapterにwrapする
     * @param PgSQLInterface $pgsql
     * @return PgSQLAdapter
     */
    public static function wrap(PgSQLInterface $pgsql): PgSQLAdapter
    {
        if ($pgsql instanceof PgSQLAdapter) {
            return $pgsql;
        }
        $self = new PgSQLAdapter();
        $self->setPdo($pgsql->getPDO());
        return $self;
    }

    /**
     * PgSQLAdapter constructor.
     */
    protected function __construct()
    {
        // do nothing
    }

    /**
     * PgSQLAdapter constructor.
     * @param string $host
     * @param string $dbName
     * @param string $user
     * @param string $password
     */
    protected function setConfig(string $host, string $dbName, string $user, string $password): void
    {
        $this->host = $host;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param PDO $pdo
     */
    protected function setPdo(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        if (is_null($this->pdo)) {
            list($host, $port) = NameResolver::resolveHostAndPort($this->host);
            $dsn = 'pgsql:host=' . $host . ';dbname=' . $this->dbName;
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
                $this->user,
                $this->password,
                $options
            );
        }
        return $this->pdo;
    }
}