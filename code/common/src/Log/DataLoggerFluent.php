<?php


namespace Gustav\Common\Log;


use Fluent\Logger\Entity;
use Fluent\Logger\FluentLogger;
use Fluent\Logger\PackerInterface;

/**
 * Class DataLoggerFluent
 * @package Gustav\Common\Log
 */
class DataLoggerFluent extends BaseDataLogger
{
    /**
     * @var DataLoggerInterface
     */
    private static $theInstance = null;

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
        parent::__construct();

        $this->logger = new FluentLogger(
            $host,
            $port,
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
     * 貯めたログのflush
     * DBのトランザクションでコミットしたときを想定
     */
    public function flush(): void
    {
        if ($this->hasEntity()) {
            foreach ($this->getEntityList() as $entity) {
                $this->logger->post2($entity);
            }
        }
        $this->clearEntityList();
    }
}
