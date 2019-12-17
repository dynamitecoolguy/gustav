<?php


namespace Gustav\Common\Adapter;

use \Exception;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Network\NameResolver;
use PDO;
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
     * MySQLAdapter constructor.
     * @param ApplicationConfigInterface $config
     * @param bool $forMaster
     * @throws ConfigException
     */
   public function __construct(ApplicationConfigInterface $config, bool $forMaster)
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
        $this->pdo = new PDO(
            $dsn,
            $config->getValue('mysql', 'user'),
            $config->getValue('mysql', 'password'),
            $options
        );
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
     *  buffered queryを有効にする (少量レコード取得用: デフォルト動作)
     */
    public function setBufferedMode(): void
    {
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    }

    /**
     *  buffered queryを無効にする (大量レコード取得用)
     */
    public function setUnbufferedMode(): void
    {
        $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    }

    /**
     * @param string $statement
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function prepare(string $statement): PDOStatement
    {
        $statement = $this->pdo->prepare($statement);
        if ($statement === false) {
            throw new DatabaseException('prepare return false');
        }
        return $statement;
    }

    /**
     * @param $statement
     * @param array|null $params
     * @throws DatabaseException
     */
    public function execute($statement, ?array $params = null): void
    {
        if ($statement instanceof PDOStatement) {
            $statement->execute($params);
        } elseif (is_string($statement)) {
            $pdoStatement = $this->prepare($statement);
            $pdoStatement->execute($params);
        } else {
            throw new DatabaseException('statement is unexpected type');
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

            throw new DatabaseException('exception occurred in transaction section', 0, $e);
        }
    }
}
