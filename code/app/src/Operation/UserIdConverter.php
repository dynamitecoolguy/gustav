<?php


namespace Gustav\App\Operation;


use Gustav\App\AppRedisKeys;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\OperationException;
use Gustav\Common\Operation\MaximumLengthSequence;

/**
 * 公開IDは、1〜8,589,934,591 (2^33 - 1)までの数値を文字列にしたものである.
 * Class UserIdConverter
 * @package Gustav\App\Operation
 */
class UserIdConverter
{
    const OPEN_ID_P = 33;
    const OPEN_ID_Q = 13;
    const OPEN_ID_INIT_VALUE = '1835215621';

    const TRANSFER_CODE_P = 41;
    const TRANSFER_CODE_Q = 20;
    const TRANSFER_CODE_INIT_VALUE = '1125937695311';

    /**
     * ユーザーIDを公開IDに変換する
     * @param RedisInterface $redis
     * @param int $userId
     * @return string
     * @throws OperationException
     */
    public static function userIdToOpenId(RedisInterface $redis, int $userId): string
    {
        // RedisInterfaceをRedisAdapterにする
        $redisAdapter = RedisAdapter::wrap($redis);

        $value = static::userIdToValue(
            $redisAdapter,
            $userId,
            self::OPEN_ID_P,
            self::OPEN_ID_Q,
            self::OPEN_ID_INIT_VALUE,
            AppRedisKeys::KEY_OPEN_ID
        );

        return substr('000000000' . $value, -10, 10);
    }

    /**
     * ユーザーIDを引き継ぎコードに変換する
     * @param RedisInterface $redis
     * @param int $userId
     * @return string
     * @throws OperationException
     */
    public static function userIdToTransferCode(RedisInterface $redis, int $userId): string
    {
        // RedisInterfaceをRedisAdapterにする
        $redisAdapter = RedisAdapter::wrap($redis);

        $value = static::userIdToValue(
            $redisAdapter,
            $userId,
            self::TRANSFER_CODE_P,
            self::TRANSFER_CODE_Q,
            self::TRANSFER_CODE_INIT_VALUE,
            AppRedisKeys::KEY_TRANSFER_CODE
        );

        $value35 = gmp_strval(gmp_init($value), 35);
        return substr('00000000' . strtolower(str_replace(['i', 'o'], ['z', '-'], $value35)), -8, 8);
    }

    /**
     * ユーザーIDをM系列で数値に変換する
     * @param RedisAdapter $redisAdapter
     * @param int $userId
     * @param int $p
     * @param int $q
     * @param int $initValue
     * @param string $redisKey
     * @return string
     * @throws OperationException
     */
    private static function userIdToValue(
        RedisAdapter $redisAdapter,
        int $userId,
        int $p,
        int $q,
        int $initValue,
        string $redisKey
    ): string
    {
        $cached = $redisAdapter->get($redisKey);

        MaximumLengthSequence::setParameter($p, $q, $initValue);
        if ($cached === false) {
            $sequencer = new MaximumLengthSequence($userId);
        } else {
            $sequencer = new MaximumLengthSequence($userId, $cached[0], $cached[1]);
        }
        $value = $sequencer->getValue();

        $redisAdapter->set($redisKey, [$userId, $value]);

        return $value;
    }

    /**
     * instanceは必要無いのでprotectedなconstructor
     * UserIdConverter constructor.
     */
    protected function __construct()
    {
        // dummy
    }
}
