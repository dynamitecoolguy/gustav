<?php


namespace Gustav\Common;

use DI\Container;
use Gustav\Common\Logic\ExecutorInterface;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelInterface;
use ReflectionClass;
use ReflectionException;

/**
 * Class BaseDispatcher
 * @package Gustav\Common
 */
class BaseDispatcher implements DispatcherInterface
{
    /**
     * @var ?array
     */
    private static $dispatchTable = null;

    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [];
    }

    /**
     * 識別コート、Model、Executorのセットを登録する
     */
    public static function registerModels(): void
    {
        self::$dispatchTable = array_reduce(
            static::getModelAndExecutor(),
            function ($carry, $record) {
                list($chunkId, $modelClass, $executorClass) = $record;

                ModelClassMap::registerModel($chunkId, $modelClass);

                $carry[$modelClass] = $executorClass;

                return $carry;
            },
            []
        );
    }

    /**
     * dispatchTableの初期化
     */
    public static function resetDispatchTable(): void
    {
        self::$dispatchTable = null;
    }

    /**
     * @return array
     */
    public static function getDispatchTable(): array
    {
        return self::$dispatchTable;
    }

    /**
     * BaseDispatcher constructor.
     */
    public function __construct()
    {
        if (is_null(self::$dispatchTable)) {
            static::registerModels();
        }
    }

    /**
     * @param Container $container
     * @param ModelChunk $requestObject
     * @return ModelInterface|null
     * @throws ModelException
     */
    public function dispatch(Container $container, ModelChunk $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();

        $class = get_class($request);
        if (!isset(self::$dispatchTable[$class])) {
            throw new ModelException('Executor is not registered');
        }

        $executorClass = self::$dispatchTable[get_class($request)];
        try {
            $refClass = new ReflectionClass($executorClass);
            if (!$refClass->isSubclassOf(ExecutorInterface::class)) {
                throw new ModelException('Executor is not instance of ExecutorInterface');
            }
            $method = $refClass->getMethod('getInstance');
            $executor = $method->invoke(null);
            if (!($executor instanceof ExecutorInterface)) {
                throw new ModelException('Executor is not instance of ExecutorInterface');
            }
        } catch (ReflectionException $e) {
            throw new ModelException('ReflectionException occurred', 0, $e);
        }
        return $executor->execute($container, $requestObject);
    }
}
