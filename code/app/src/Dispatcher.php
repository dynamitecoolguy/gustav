<?php


namespace Gustav\App;


use Gustav\Common\Model\ModelInterface;

class Dispatcher
{
    /**
     * @param ModelInterface $request
     * @return ModelInterface|null
     */
    public static function dispatch(ModelInterface $request): ?ModelInterface
    {
        return null;
    }
}