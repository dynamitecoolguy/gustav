<?php


namespace Gustav\App;

use DI\Container;
use Gustav\Common\Model\ModelInterface;

class Dispatcher implements DispatcherInterface
{
    /**
     * @param Container $container
     * @param ModelInterface $request
     * @return ModelInterface|null
     */
    public function dispatch(Container $container, ModelInterface $request): ?ModelInterface
    {
        return null;
    }
}