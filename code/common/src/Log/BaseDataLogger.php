<?php


namespace Gustav\Common\Log;

/**
 * Class BaseDataLogger
 * @package Gustav\Common\Log
 */
class BaseDataLogger implements DataLoggerInterface
{
    /**
     * @var EntityUs[]
     */
    private $entityList;

    /**
     * BaseDataLogger constructor.
     */
    protected function __construct()
    {
        $this->entityList = [];
    }

    /**
     * ログ用データの追加.
     * ただし、 flushするまでは出力されない
     * @param string $tag
     * @param float $timestamp
     * @param array $dataHash
     */
    public function add(string $tag, float $timestamp, array $dataHash): void
    {
        $this->entityList[] = new EntityUs($tag, $dataHash, $timestamp);
    }

    /**
     * 貯めたログのflush
     * DBのトランザクションでコミットしたときを想定
     */
    public function flush(): void
    {
        // You must overwrite this method to write logs to somewhere.

        $this->clearEntityList();
    }

    /**
     * 貯めたログを出力せずにクリア.
     * DBのトランザクションでロールバックしたときを想定
     */
    public function clear(): void
    {
        $this->clearEntityList();
    }

    /**
     * entityListが空かどうか
     * @return bool
     */
    protected function hasEntity(): bool
    {
        return isset($this->entityList[0]);
    }

    /**
     * entityListのクリア
     */
    protected function clearEntityList(): void
    {
        $this->entityList = [];
    }

    /**
     * @return EntityUs[]
     */
    protected function getEntityList(): array
    {
        return $this->entityList;
    }
}
