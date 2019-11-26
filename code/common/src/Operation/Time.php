<?php


namespace Gustav\Common\Operation;

/**
 * Class Time
 * @package Gustav\Common\Operation
 */
class Time
{
    /**
     * @var float
     */
    private static $now = null;

    /**
     * リクエスト時にセットされ、それ以後は同じ時間を返す.
     * @return float
     */
    public static function now(): float
    {
        if (is_null(static::$now)) {
            static::$now = microtime(true);
        }
        return static::$now;
    }
}