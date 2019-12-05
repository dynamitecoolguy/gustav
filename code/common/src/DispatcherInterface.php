<?php


namespace Gustav\Common;


use DI\Container;
use Gustav\Common\Model\ModelInterface;

/**
 * Interface DispatcherInterface
 * @package Gustav\App
 */
interface DispatcherInterface
{
    /**
     * @param int            $version       // フォーマットバージョン
     * @param Container      $container     // DI\Container
     * @param ModelInterface $request       // リクエストオブジェクト
     * @return ModelInterface|null          // リザルト。必要ない場合はnull
     */
    public function dispatch(int $version, Container $container, ModelInterface $request): ?ModelInterface;
}