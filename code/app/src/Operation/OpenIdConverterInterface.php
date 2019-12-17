<?php


namespace Gustav\App\Operation;


use Gustav\Common\Adapter\RedisInterface;

/**
 * Interface OpenIdConverterInterface
 * @package Gustav\App\Operation
 */
interface OpenIdConverterInterface
{
    /**
     * @param RedisInterface $redis
     * @param int $userId
     * @return string
     */
    public function userIdToOpenId(RedisInterface $redis, int $userId): string;
}