<?php


namespace Gustav\Common\Adapter;

use \Exception;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Exception\DuplicateEntryException;
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
    const CACHE_TIMEOUT = 600;

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
     * @var bool Transactionの中かどうか
     */
    private $inTransaction = false;

    /**
     * @var bool executeWithTransactionがrollbackされるかどうか
     */
    private $transactionCancelled = false;

    /** @var RedisAdapter|null キャッシュに使うRedis*/
    private $redisAdapter = null;

    /** @var string[] 無効にするキャッシュキー */
    private $invalidatedKeys = [];

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
        $useCache = $config->getValue('mysql', 'usecache', 'false');

        $self = new static();
        $self->setConfig($hostMaster, $hostSlave, $dbName, $user, $password);

        // Redisによるキャッシュを行う場合は、Redisへの接続準備をしておく
        if ($useCache && $useCache != 'false') {
            $self->redisAdapter = RedisAdapter::create($config);
        }
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

        $self = new static();
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
     * MySQLAdapter constructor.
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
     * MasterDB用操作を許可する
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
            if (isset($e->errorInfo[1]) && 1062 == $e->errorInfo[1]) {
                throw new DuplicateEntryException(
                    "Duplicated entry",
                    DatabaseException::DUPLICATE_ENTRY,
                    $e
                );
            }
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

        $this->transactionCancelled = false;
        $this->beginTransaction();

        try {
            $result = $callable($this, $option);

            if (!$this->transactionCancelled) {
                $this->commit();
                if (!is_null($succeeded)) {
                    $succeeded($this, $result, $option);
                }
            } else {
                $this->rollBack();
                if (!is_null($failed)) {
                    $failed($this, $option);
                }
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

        if ($this->inTransaction) {
            throw new DatabaseException(
                "Transaction could not be nested",
                DatabaseException::TRANSACTION_NESTED
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
        $this->inTransaction = true;
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

        // transaction中に無効にされたキャッシュがあれば、それを反映する
        if (!is_null($this->redisAdapter) && !empty($this->invalidatedKeys)) {
            $this->redisAdapter->del(array_keys($this->invalidatedKeys));
            $this->invalidatedKeys = [];
        }

        try {
            $this->inTransaction = false;
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

        // transaction中に無効にされたキャッシュがあれば、それを無効にすることを無効にする
        if (!is_null($this->redisAdapter)) {
            $this->invalidatedKeys = [];
        }

        try {
            $this->inTransaction = false;
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
     * Transaction内かどうか
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    /**
     * Transactionをrollbackさせる
     */
    public function cancelTransaction(): void
    {
        if ($this->inTransaction) {
            $this->transactionCancelled = true;
        }
    }

    /**
     * @param $statement
     * @param array|null $params
     * @param array|int|null $timestampField
     * @return array|null
     * @throws DatabaseException
     */
    public function fetch($statement, ?array $params = null, $timestampField = null): ?array
    {
        $pdoStatement = $this->wrapStatement($statement, $params);
        try {
            $pdoStatement->execute();
            $result = $pdoStatement->fetch(PDO::FETCH_NUM);
            return ($result === false) ? null : $this->parseTimestamp($result, $timestampField);
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Execution statement(${statement}) failed",
                DatabaseException::EXECUTION_FAILED,
                $e
            );
        }
    }

    /**
     * @param string $key
     * @param $statement
     * @param array|null $params
     * @param array|int|null $timestampField
     * @return array|null
     * @throws DatabaseException
     */
    public function cachedFetch($key, $statement, ?array $params = null, $timestampField = null): ?array
    {
        // Transactionの中、あるいは、Redisがなければ普通のfetchと同じ
        if (is_null($this->redisAdapter) || $this->inTransaction) {
            return $this->fetch($statement, $params, $timestampField);
        }

        // キャッシュにあるのでそこから取ってくる
        if ($this->redisAdapter->exists($key)) {
            return $this->redisAdapter->get($key);
        }

        // DBから取得
        $result = $this->fetch($statement, $params, $timestampField);

        // キャッシュに保存
        if (!is_null($result)) {
            $this->redisAdapter->setex($key, self::CACHE_TIMEOUT, $result);
        }

        return $result;
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
            $pdoStatement->execute();
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
            $pdoStatement = $this->prepare($statement);
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
            if (!is_null($params)) {
                $this->bindArray($pdoStatement, $params);
            }
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Binding failed",
                DatabaseException::BIND_ERROR,
                $e
            );
        }
        return $pdoStatement;
    }

    /**
     * @param PDOStatement $statement
     * @param array $params
     */
    private function bindArray(PDOStatement $statement, array $params): void
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

    /**
     * キャッシュのキーを無効にする
     * @param string $key
     */
    public function invalidateKey(string $key)
    {
        if (!is_null($this->redisAdapter)) {
            if ($this->isInTransaction()) {
                $this->invalidatedKeys[$key] = 1;
            } else {
                $this->redisAdapter->del($key);
            }
        }
    }

    /**
     * MySQLから取得したレコード内のtimestampをunix timestampに変更する
     * @param array $record
     * @param array|int $timestampField
     * @return array
     */
    public function parseTimestamp(array $record, $timestampField)
    {
        if (is_int($timestampField)) {
            $timestampArray = [$timestampField];
        } elseif (is_array($timestampField)){
            $timestampArray = $timestampField;
        } else {
            return $record;
        }

        if (is_array($record[0])) {
            $result = [];
            foreach ($record as $recordItem) {
                $result[] = $this->parseTimestamp($recordItem, $timestampArray);
            }
        } else {
            foreach ($timestampArray as $timestampIndex) {
                if (isset($record[$timestampIndex])) {
                    $t = strtotime($record[$timestampIndex]);
                    if ($t === false || $t < 0) {
                        $t = 0;
                    }
                    $record[$timestampIndex] = $t;
                }
            }
            $result = $record;
        }
        return $result;
    }
}
