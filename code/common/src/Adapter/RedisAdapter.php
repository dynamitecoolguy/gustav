<?php


namespace Gustav\Common\Adapter;

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
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
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
