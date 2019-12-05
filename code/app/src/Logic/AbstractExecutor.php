<?php


namespace Gustav\App\Logic;

use Gustav\Common\Logic\ExecutorInterface;

abstract class AbstractExecutor implements ExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function getInstance(): ExecutorInterface
    {
        return new static();
    }
}