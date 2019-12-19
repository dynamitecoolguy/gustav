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
class MySQLAdapter implements MySQLInterface, MySQLMasterInterface
{
    /**
     * @var PDO PDO Object
     */
    private $pdo;

    /**
     * @var bool Master接続かどうか
     */
    private $forMaster;

    /**
     * @param ApplicationConfigInterface $config
     * @param bool $forMaster
     * @return static
     * @throws ConfigException
     * @throws DatabaseException
     */
    public static function create(ApplicationConfigInterface $config, bool $forMaster): MySQLAdapter
    {
        $hostKey = $forMaster ? 'hostm' : 'host';
        list($host, $port) = NameResolver::resolveHostAndPort($config->getValue('mysql', $hostKey));
        $dsn = 'mysql:host=' . $host . ';dbname=' . $config->getValue('mysql', 'dbname');
        if ($port > 0) {
            $dsn .= ';port=' . $port;
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        try {
            $pdo = new PDO(
                $dsn,
                $config->getValue('mysql', 'user'),
                $config->getValue('mysql', 'password'),
                $options
            );
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Connection failed (dsn:${dsn})",
                DatabaseException::CONNECTION_FAILED,
                $e
            );
        }

        return new static($pdo, $forMaster);
    }

    /**
     * MySQLInterfaceをMySQLAdapterにwrapする
     * @param MySQLInterface $mysql
     * @param bool $forMaster
     * @return MySQLAdapter
     */
    public static function wrap(MySQLInterface $mysql, bool $forMaster = false): MySQLAdapter
    {
        return ($mysql instanceof MySQLAdapter) ? $mysql : new static($mysql->getPDO(), $forMaster);
    }

    /**
     * MySQLAdapter constructor.
     * @param PDO $pdo
     * @param bool $forMaster
     */
   public function __construct(PDO $pdo, bool $forMaster)
    {
        $this->pdo = $pdo;
        $this->forMaster = $forMaster;
    }

    /**
     * @return PDO
     */
    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    /**
     * @return bool
     */
    public function forMaster(): bool
    {
        return $this->forMaster;
    }

    /**
     * buffered queryを有効にする (少量レコード取得用: デフォルト動作)
     * @throws DatabaseException
     */
    public function setBufferedMode(): void
    {
        try {
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
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
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
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
            $prepared = $this->pdo->prepare($statement);
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
        if ($statement instanceof PDOStatement) {
            try {
                $statement->execute($params);
            } catch (PDOException $e) {
                throw new DatabaseException(
                    "Execution failed",
                    DatabaseException::EXECUTION_FAILED,
                    $e
                );
            }
        } elseif (is_string($statement)) {
            $pdoStatement = $this->prepare($statement);
            try {
                $pdoStatement->execute($params);
            } catch (PDOException $e) {
                throw new DatabaseException(
                    "Execution statement(${statement}) failed",
                    DatabaseException::EXECUTION_FAILED,
                    $e
                );
            }
        } else {
            throw new DatabaseException(
                "Execution statement is invalid",
                DatabaseException::EXECUTION_FAILED
            );
        }
    }

    /**
     * @return int
     */
    public function lastInsertId(): int
    {
        return $this->pdo->lastInsertId();
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
        if (!$this->forMaster) {
            throw new DatabaseException('executeWithTransaction is for master only');
        }

        try {
            $this->pdo->beginTransaction();

            $result = $callable($this, $option);

            $this->pdo->commit();

            if (!is_null($succeeded)) {
                $succeeded($this, $result, $option);
            }

            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();

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
}
