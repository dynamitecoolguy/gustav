<?php


namespace Gustav\Common\Logic;


use DI\Container;
use Gustav\Common\Model\ModelInterface;

/**
 * Interface ExecutorInterface
 * @package Gustav\App\Logic
 */
interface ExecutorInterface
{
    /**
     * インスタンス作成
     * @return ExecutorInterface
     */
    public function getInstance(): ExecutorInterface;

    /**
     * @param int            $version       // フォーマットバージョン
     * @param Container      $container     // DI\Container
     * @param ModelInterface $request       // リクエストオブジェクト
     * @return ModelInterface|null
     */
    public function execute(int $version, Container $container, ModelInterface $request): ?ModelInterface;
}
