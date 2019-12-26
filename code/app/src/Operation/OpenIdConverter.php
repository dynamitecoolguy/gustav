<?php


namespace Gustav\App\Operation;


use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\OperationException;
use Gustav\Common\Operation\MaximumLengthSequence;

/**
 * 公開IDは、1〜8,589,934,591 (2^33 - 1)までの数値を文字列にしたものである.
 * Class OpenIdConverter
 * @package Gustav\App\Operation
 */
class OpenIdConverter implements OpenIdConverterInterface
{
    const P = 33;
    const Q = 13;
    const INIT_VALUE = '1835215621';

    /**
     * ユーザーIDをM系列で数値に変換する
     * @param RedisInterface $redis
     * @param int $userId
     * @return string
     * @throws OperationException
     */
    public function userIdToOpenId(RedisInterface $redis, int $userId): string
    {
        // RedisInterfaceをRedisAdapterにする
        $redisAdapter = RedisAdapter::wrap($redis);

        $cached = $redisAdapter->get(AppRedisKeys::KEY_OPEN_ID);

        MaximumLengthSequence::setParameter(self::P, self::Q, self::INIT_VALUE);
        if ($cached === false) {
            $sequencer = new MaximumLengthSequence($userId);
        } else {
            $sequencer = new MaximumLengthSequence($userId, $cached[0], $cached[1]);
        }
        $value = $sequencer->getValue();

        $redisAdapter->set(AppRedisKeys::KEY_OPEN_ID, [$userId, $value]);

        return substr('000000000' . $value, -10, 10);
    }
}
