<?php


namespace Gustav\Common;

use DI\Container;
use Gustav\Common\Logic\ExecutorInterface;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelInterface;

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
     * BaseDispatcher constructor.
     */
    public function __construct()
    {
        if (is_null(self::$dispatchTable)) {
            static::registerModels();
        }
    }

    /**
     * @param int $version
     * @param Container $container
     * @param ModelInterface $request
     * @return ModelInterface|null
     * @throws ModelException
     */
    public function dispatch(int $version, Container $container, ModelInterface $request): ?ModelInterface
    {
        $class = get_class($request);
        if (!isset(self::$dispatchTable[$class])) {
            throw new ModelException('Executor is not registered');
        }

        $executorClass = self::$dispatchTable[get_class($request)];
        $executor = call_user_func([$executorClass, 'getInstance']);
        if ($executor instanceof ExecutorInterface) {
            return $executor->execute($version, $container, $request);
        }

        throw new ModelException('Executor is not instance of ExecutorInterface');
    }
}
