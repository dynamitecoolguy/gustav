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
     * @var Redis redisObject
     */
    private $redis;

    /**
     * RedisAdapter constructor.
     * @param ApplicationConfigInterface $config
     * @throws ConfigException
     */
    public function __construct(ApplicationConfigInterface $config)
    {
        list($host, $port) = NameResolver::resolveHostAndPort($config->getValue('redis', 'host'));
        $redis = new Redis();
        if ($port > 0) {
            $redis->connect($host, $port);
        } else {
            $redis->connect($host);
        }
        $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

        $this->redis = $redis;
    }

    /**
     * @return Redis
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * @param string $key
     * @return bool|mixed|string
     */
    public function get(string $key)
    {
        return $this->redis->get($key);
    }

    /**
     * @param string $key
     */
    public function del(string $key): void
    {
        $this->redis->del($key);
    }

    /**
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void
    {
        $this->redis->set($key, $value);
    }
}
