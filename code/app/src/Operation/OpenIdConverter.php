<?php


namespace Gustav\App\Operation;


use Gustav\App\RedisKeys;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
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
    const INIT_VALUE = 1835215621;

    /**
     * @var bool M系列パラメータを初期化したかどうか
     */
    private static $parameterInited = false;

    /**
     * M系列のパラメータセット
     */
    private static function checkParameter()
    {
        if (!self::$parameterInited) {
            MaximumLengthSequence::setParameter(self::P, self::Q, self::INIT_VALUE);
            self::$parameterInited = true;
        }
    }

    /**
     * ユーザーIDをM系列で数値に変換する
     * @param RedisInterface $redis
     * @param int $userId
     * @return string
     */
    public function userIdToOpenId(RedisInterface $redis, int $userId): string
    {
        // M系列のパラメータセット確認
        self::checkParameter();

        // RedisInterfaceをRedisAdapterにする
        $redisAdapter = ($redis instanceof RedisAdapter) ? $redis : new RedisAdapter($redis->getRedis());

        $cached = $redisAdapter->get(RedisKeys::KEY_OPEN_ID);

        if ($cached === false) {
            $sequencer = new MaximumLengthSequence($userId);
        } else {
            $sequencer = new MaximumLengthSequence($userId, $cached[0], $cached[1]);
        }
        $value = $sequencer->getValue();

        $redisAdapter->set(RedisKeys::KEY_OPEN_ID, [$userId, $value]);

        return substr('000000000' . strval($value), -10, 10);
    }
}
