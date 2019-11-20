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
}
