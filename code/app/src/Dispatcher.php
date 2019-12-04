<?php


namespace Gustav\App;

use DI\Container;
use Gustav\App\Logic\ExecutorInterface;
use Gustav\App\Logic\RegistrationExecutor;
use Gustav\App\Model\RegistrationModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelInterface;

class Dispatcher implements DispatcherInterface
{
    private static $dispatchTable = [];

    public static function registerModels(): void
    {
        array_map(
            function ($record) {
                list($chunkId, $modelClass, $executorClass) = $record;

                ModelClassMap::registerModel($chunkId, $modelClass);

                self::$dispatchTable[$modelClass] = $executorClass;
            },
            [
                ['REG', RegistrationModel::class, RegistrationExecutor::class]
            ]
        );
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

        $executor = self::$dispatchTable[get_class($request)];
        if ($executor instanceof ExecutorInterface) {
            return $executor->execute($version, $container, $request);
        }

        throw new ModelException('Executor is not instance of ExecutorInterface');
    }
}
