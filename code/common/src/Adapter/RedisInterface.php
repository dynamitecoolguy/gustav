<?php


namespace Gustav\Common\Adapter;

use Redis;

/**
 * Interface RedisInterface
 * @package Gustav\Common\Adapter
 */
interface RedisInterface
{
    /**
     * @return Redis
     */
    public function getRedis(): Redis;
}