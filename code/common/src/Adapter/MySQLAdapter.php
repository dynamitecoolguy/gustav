<?php


namespace Gustav\Common\Adapter;

use \Exception;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Network\NameResolver;
use PDO;
use PDOException;
use PDOStatement;

/**
 * Class MySQLAdapter
 * @package Gustav\Common\Adapter
 */
class MySQLAdapter implements MySQLInterface
{
    /**
     * @var string ホスト名 または ホスト名:ポート
     */
    private $hostMaster = '';

    /**
     * @var string ホスト名 または ホスト名:ポート
     */
    private $hostSlave = '';

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
     * @var bool Masterかどうか
     */
    private $isMaster = false;

    /**
     * @var ?PDO PDO Object
     */
    private $pdo = null;

    /**
     * @param ApplicationConfigInterface $config
     * @return static
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): MySQLAdapter
    {
        $hostMaster = $config->getValue('mysql', 'hostm');
        $hostSlave = $config->getValue('mysql', 'host');
        $dbName = $config->getValue('mysql', 'dbname');
        $user = $config->getValue('mysql', 'user');
        $password = $config->getValue('mysql', 'password');

        $self = new MySQLAdapter();
        $self->setConfig($hostMaster, $hostSlave, $dbName, $user, $password);
        return $self;
    }

    /**
     * MySQLInterfaceをMySQLAdapterにwrapする
     * @param MySQLInterface $mysql
     * @param bool $isMaster
     * @return MySQLAdapter
     */
    public static function wrap(MySQLInterface $mysql, bool $isMaster): MySQLAdapter
    {
        if ($mysql instanceof MySQLAdapter) {
            if ($isMaster) {
                $mysql->setMaster();
            }
            return $mysql;
        }

        $self = new MySQLAdapter();
        $self->setPdo($mysql->getPDO(), $isMaster);
        return $self;
    }

    /**
     * MySQLAdapter constructor.
     */
    protected function __construct()
    {
        // do nothing
    }

    /**
     * PgSQLAdapter constructor.
     * @param string $hostMaster
     * @param string $hostSlave
     * @param string $dbName
     * @param string $user
     * @param string $password
     */
    protected function setConfig(string $hostMaster, string $hostSlave, string $dbName, string $user, string $password): void
    {
        $this->hostMaster = $hostMaster;
        $this->hostSlave = $hostSlave;
        $this->dbName = $dbName;
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * @param PDO $pdo
     * @param bool $isMaster
     */
    protected function setPdo(PDO $pdo, bool $isMaster): void
    {
        $this->pdo = $pdo;
        $this->isMaster = $isMaster;
    }

    /**
     */
    public function setMaster(): void
    {
        $this->isMaster = true;
    }

    /**
     * @return PDO
     * @throws DatabaseException
     */
    public function getPDO(): PDO
    {
        if (is_null($this->pdo)) {
            $host = $this->isMaster ? $this->hostMaster : $this->hostSlave;
            list($host, $port) = NameResolver::resolveHostAndPort($host);
            $dsn = 'mysql:host=' . $host . ';dbname=' . $this->dbName;
            if ($port > 0) {
                $dsn .= ';port=' . $port;
            }
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            try {
                $this->pdo = new PDO(
                    $dsn,
                    $this->user,
                    $this->password,
                    $options
                );
            } catch (PDOException $e) {
                throw new DatabaseException(
                    "Connection failed (dsn:${dsn})",
                    DatabaseException::CONNECTION_FAILED,
                    $e
                );
            }
        }

        return $this->pdo;
    }

    /**
     * @return bool
     */
    public function isMaster(): bool
    {
        return $this->isMaster;
    }

    /**
     * buffered queryを有効にする (少量レコード取得用: デフォルト動作)
     * @throws DatabaseException
     */
    public function setBufferedMode(): void
    {
        try {
            $this->getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fail to enable buffer mode",
                DatabaseException::BUFFERED_MODE_FAILED,
                $e
            );
        }
    }

    /**
     * buffered queryを無効にする (大量レコード取得用)
     * @throws DatabaseException
     */
    public function setUnbufferedMode(): void
    {
        try {
            $this->getPdo()->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Fail to disable buffer mode",
                DatabaseException::BUFFERED_MODE_FAILED,
                $e
            );
        }
    }

    /**
     * @param string $statement
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function prepare(string $statement): PDOStatement
    {
        try {
            $prepared = $this->getPdo()->prepare($statement);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Statement(${statement}) can't be prepared",
                DatabaseException::PREPARING_FAILED,
                $e
            );
        }
        return $prepared;
    }

    /**
     * @param $statement
     * @param array|null $params
     * @throws DatabaseException
     */
    public function execute($statement, ?array $params = null): void
    {
        $pdoStatement = $this->wrapStatement($statement, $params);
        try {
            $pdoStatement->execute($params);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execution statement failed",
                DatabaseException::EXECUTION_FAILED,
                $e
            );
        }
    }

    /**
     * @return int
     * @throws DatabaseException
     */
    public function lastInsertId(): int
    {
        return $this->getPdo()->lastInsertId();
    }

    /**
     * トランザクション付き処理
     * @param callable $callable         Transaction内で実行されるcallable. 引数は($this, $option)
     * @param mixed|null $option         関数の最後に与えられるオプション
     * @param callable|null $succeeded   commit後に呼び出されるcallable. 引数は($this, $result, $option)
     * @param callable|null $failed      rollback後に呼び出されるcallable. 引数は($this, $option)
     * @return mixed                     $callableの返り値
     * @throws DatabaseException
     */
    public function executeWithTransaction(callable $callable, $option = null, ?callable $succeeded = null, ?callable $failed = null)
    {
        if (!$this->isMaster) {
            throw new DatabaseException(
                'executeWithTransaction is for master only',
                DatabaseException::DATABASE_IS_SLAVE
            );
        }

        $this->beginTransaction();

        try {
            $result = $callable($this, $option);

            $this->commit();

            if (!is_null($succeeded)) {
                $succeeded($this, $result, $option);
            }

            return $result;
        } catch (Exception $e) {
            $this->rollBack();

            if (!is_null($failed)) {
                $failed($this, $option);
            }

            if (!($e instanceof DatabaseException)) {
                throw new DatabaseException(
                    "Transaction failed",
                    DatabaseException::TRANSACTION_FAILED,
                    $e
                );
            }
            throw $e; // rethrow DatabaseException
        }
    }

    /**
     * @throws DatabaseException
     */
    public function beginTransaction(): void
    {
        if (!$this->isMaster) {
            throw new DatabaseException(
                'beginTransaction is for master only',
                DatabaseException::DATABASE_IS_SLAVE
            );
        }

        try {
            $this->getPdo()->beginTransaction();
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Transaction could not be started",
                DatabaseException::TRANSACTION_FAILED,
                $e
            );
        }
    }

    /**
     * @throws DatabaseException
     */
    public function commit(): void
    {
        if (!$this->isMaster) {
            throw new DatabaseException(
                'commit is for master only',
                DatabaseException::DATABASE_IS_SLAVE
            );
        }

        try {
            $this->getPdo()->commit();
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Transaction could not be started",
                DatabaseException::TRANSACTION_FAILED,
                $e
            );
        }
    }

    /**
     * @throws DatabaseException
     */
    public function rollBack(): void
    {
        if (!$this->isMaster) {
            throw new DatabaseException(
                'rollBack is for master only',
                DatabaseException::DATABASE_IS_SLAVE
            );
        }

        try {
            $this->getPdo()->rollBack();
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Transaction could not be started",
                DatabaseException::TRANSACTION_FAILED,
                $e
            );
        }
    }

    /**
     * @param $statement
     * @param array|null $params
     * @return array|null
     * @throws DatabaseException
     */
    public function fetch($statement, ?array $params = null): ?array
    {
        $pdoStatement = $this->wrapStatement($statement, $params);
        try {
            $pdoStatement->execute($params);
            return $pdoStatement->fetch(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execution statement(${statement}) failed",
                DatabaseException::EXECUTION_FAILED,
                $e
            );
        }
    }

    /**
     * @param $statement
     * @param array|null $params
     * @return array
     * @throws DatabaseException
     */
    public function fetchAll($statement, ?array $params = null): array
    {
        $pdoStatement = $this->wrapStatement($statement, $params);
        try {
            $pdoStatement->execute($params);
            return $pdoStatement->fetchAll(PDO::FETCH_NUM);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execution statement failed",
                DatabaseException::EXECUTION_FAILED,
                $e
            );
        }
    }

    /**
     * StatementをPDOStatementにし、パラメータにbindして返す
     * @param mixed $statement
     * @param array|null $params
     * @return PDOStatement
     * @throws DatabaseException
     */
    private function wrapStatement($statement, ?array $params): PDOStatement
    {
        // Prepare
        if (is_string($statement)) {
            try {
                $pdoStatement = $this->prepare($statement);
            } catch (PDOException $e) {
                throw new DatabaseException(
                    "Statement(${statement}) could not be prepared",
                    DatabaseException::STATEMENT_COULD_NOT_BE_PREPARED,
                    $e
                );
            }
        } elseif ($statement instanceof PDOStatement) {
            $pdoStatement = $statement;
        } else {
            throw new DatabaseException(
                "Execution statement is invalid",
                DatabaseException::STATEMENT_IS_ILLEGAL
            );
        }

        // Bind
        try {
            $this->bindArray($pdoStatement, $params);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execution failed",
                DatabaseException::BIND_ERROR,
                $e
            );
        }
        return $pdoStatement;
    }

    /**
     * @param PDOStatement $statement
     * @param array|null $params
     */
    private function bindArray(PDOStatement $statement, ?array $params): void
    {
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $param = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $param = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $param = PDO::PARAM_NULL;
            } else {
                $param = PDO::PARAM_STR;
            }
            $statement->bindValue(":${key}", $value, $param);
        }
    }

}
