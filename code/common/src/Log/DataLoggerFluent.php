<?php


namespace Gustav\Common\Log;


use Fluent\Logger\Entity;
use Fluent\Logger\FluentLogger;
use Fluent\Logger\PackerInterface;

/**
 * Class DataLoggerFluent
 * @package Gustav\Common\Log
 */
class DataLoggerFluent implements DataLoggerInterface
{
    /**
     * @var DataLoggerInterface
     */
    private static $theInstance = null;

    /**
     * @var FluentLoggerEntity[]
     */
    private $entityList;

    /**
     * @var FluentLogger
     */
    private $logger;

    /**
     * @param string $host fluentdのホスト名
     * @param int $port fluentdのポート
     * @return DataLoggerFluent
     */
    public static function getInstance(string $host, int $port): DataLoggerFluent
    {
        if (is_null(self::$theInstance)) {
            self::$theInstance = new DataLoggerFluent($host, $port);
        }

        return self::$theInstance;
    }

    /**
     * DataLoggerFluent constructor.
     * @param string $host
     * @param int $port
     */
    protected function __construct(string $host, int $port)
    {
        $this->entityList = [];
        $this->logger = new FluentLogger(
            $host,
            $port > 0 ? $port : 24224,
            [
                'max_write_retry' => 10,
                'socket_timeout' => 60,
                'connection_timeout' => 60
            ],
            new class implements PackerInterface {
                public function pack(Entity $entity) {
                    return msgpack_pack([$entity->getTag(), $entity->getTime(), $entity->getData()]);
                }
            }
        );
    }

    /**
     * ログ用データの追加.
     * ただし、 flushするまでは出力されない
     * @param string $tag
     * @param float $timestamp
     * @param array $dataHash
     */
    public function add(string $tag, float $timestamp, array $dataHash): void
    {
        $this->entityList[] = new FluentLoggerEntity($tag, $dataHash, $timestamp);
    }

    /**
     * 貯めたログのflush
     * DBのトランザクションでコミットしたときを想定
     */
    public function flush(): void
    {
        foreach ($this->entityList as $entity) {
            $this->logger->post2($entity);
        }
        $this->entityList = [];
    }

    /**
     * 貯めたログを出力せずにクリア.
     * DBのトランザクションでロールバックしたときを想定
     */
    public function clear(): void
    {
        $this->entityList = [];
    }
}
