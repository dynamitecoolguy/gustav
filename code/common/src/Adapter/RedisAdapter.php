<?php


namespace Gustav\Common\Adapter;

use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;
use Redis;

/**
 * Class RedisAdapter
 * @package Gustav\Common\Adapter
 */
class RedisAdapter implements RedisInterface
{
    /**
     * @var string Redisホスト
     */
    private $host = '';

    /**
     * @var ?Redis redisObject
     */
    private $redis = null;

    /**
     * @param ApplicationConfigInterface $config
     * @return RedisAdapter
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): RedisAdapter
    {
        $host = $config->getValue('redis', 'host');

        $self = new static();
        $self->setConfig($host);
        return $self;
    }

    /**
     * RedisInterfaceをRedisAdapterにwrapする
     * @param RedisInterface $redis
     * @return RedisAdapter
     */
    public static function wrap(RedisInterface $redis): RedisAdapter
    {
        if ($redis instanceof RedisAdapter) {
            return $redis;
        }

        $self = new static();
        $self->setRedis($redis->getRedis());
        return $self;
    }

    /**
     * RedisAdapter constructor.
     */
    public function __construct()
    {
        // do nothing
    }

    /**
     * @param string $host
     */
    protected function setConfig(string $host): void
    {
        $this->host = $host;
    }

    /**
     * @param Redis $redis
     */
    protected function setRedis(Redis $redis): void
    {
        $this->redis = $redis;
    }

    /**
     * @return Redis
     */
    public function getRedis(): Redis
    {
        if (is_null($this->redis)) {
            list($host, $port) = NameResolver::resolveHostAndPort($this->host);
            $redis = new Redis();
            if ($port > 0) {
                $redis->connect($host, $port);
            } else {
                $redis->connect($host);
            }
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

            $this->redis = $redis;
        }
        return $this->redis;
    }

    /**
     * @param string $key
     * @return bool|mixed|string
     */
    public function get(string $key)
    {
        return $this->getRedis()->get($key);
    }

    /**
     * @param string|array $key
     */
    public function del($key): void
    {
        $this->getRedis()->del($key);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void
    {
        $this->getRedis()->set($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool
    {
        $this->getRedis()->exists($key);
    }

    /**
     * @param string $key
     * @param int $ttl
     * @param $value
     */
    public function setex(string $key, int $ttl, $value): void
    {
        $this->getRedis()->set($key, $value, ['ex' => $ttl]);
    }
}
