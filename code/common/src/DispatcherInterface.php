<?php


namespace Gustav\Common;


use DI\Container;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;

/**
 * Interface DispatcherInterface
 * @package Gustav\App
 */
interface DispatcherInterface
{
    /**
     * @param Container      $container     // DI\Container
     * @param ModelChunk     $request       // リクエストオブジェクト
     * @return ModelInterface|null          // リザルト。必要ない場合はnull
     * @throws ModelException
     */
    public function dispatch(Container $container, ModelChunk $request): ?ModelInterface;
}