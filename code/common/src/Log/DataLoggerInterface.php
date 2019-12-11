<?php


namespace Gustav\Common\Log;

/**
 * Interface DataLoggerInterface
 * @package Gustav\Common\Log
 */
interface DataLoggerInterface
{
    /**
     * ログ用データの追加.
     * ただし、 flushするまでは出力されない
     * @param string $tag
     * @param float $timestamp
     * @param array $dataHash
     */
    public function add(string $tag, float $timestamp, array $dataHash): void;

    /**
     * 貯めたログのflush
     * DBのトランザクションでコミットしたときを想定
     */
    public function flush(): void;

    /**
     * 貯めたログを出力せずにクリア.
     * DBのトランザクションでロールバックしたときを想定
     */
    public function clear(): void;
}