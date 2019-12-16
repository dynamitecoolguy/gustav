<?php


namespace Gustav\Common\Logic;


use DI\Container;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * データ処理を実際に行うクラス用のインターフェイスです.
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
     * 処理を行うメソッド
     * @param Container      $container     // DI\Container
     * @param ModelChunk     $requestObject // リクエストオブジェクト
     * @return ModelInterface|null
     * @throws ModelException
     * @throws DatabaseException
     * @throws ContainerExceptionInterface
     */
    public function execute(Container $container, ModelChunk $requestObject): ?ModelInterface;
}
