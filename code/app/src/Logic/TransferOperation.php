<?php


namespace Gustav\App\Logic;


use DI\Container;
use Gustav\Common\Model\ModelInterface;

class TransferOperation extends AbstractExecutor
{

    /**
     * @inheritDoc
     */
    public function execute(int $version, Container $container, ModelInterface $request): ?ModelInterface
    {
        // TODO: Implement execute() method.
    }
}