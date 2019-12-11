<?php


namespace Gustav\Common\Logic;


use DI\Container;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
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
    public static function getInstance(): ExecutorInterface;

    /**
     * @param Container      $container     // DI\Container
     * @param ModelChunk     $requestObject // リクエストオブジェクト
     * @return ModelInterface|null
     * @throws ModelException
     */
    public function execute(Container $container, ModelChunk $requestObject): ?ModelInterface;
}
