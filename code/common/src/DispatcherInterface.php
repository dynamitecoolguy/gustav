<?php


namespace Gustav\Common;


use DI\Container;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelInterface;

/**
 * Interface DispatcherInterface
 * @package Gustav\Common
 */
interface DispatcherInterface
{
    /**
     * @param Container      $container     // DI\Container
     * @param Pack           $request       // リクエストオブジェクト
     * @return ModelInterface|null          // リザルト。必要ない場合はnull
     * @throws ModelException
     */
    public function dispatch(Container $container, Pack $request): ?ModelInterface;
}