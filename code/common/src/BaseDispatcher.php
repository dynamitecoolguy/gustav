<?php


namespace Gustav\Common;

use DI\Container;
use Exception;
use Gustav\Common\Exception\GustavException;
use Invoker\Invoker;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelInterface;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;

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
                list($packType, $modelClass, $executorCallable) = $record;

                ModelMapper::registerModel($packType, $modelClass);

                $carry[$modelClass] = $executorCallable;

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
     * @param Pack $requestObject
     * @return ModelInterface|null
     * @throws GustavException
     */
    public function dispatch(Container $container, Pack $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();

        $class = get_class($request);
        if (!isset(self::$dispatchTable[$class])) {
            throw new ModelException('Executor is not registered');
        }

        // Executorを探す
        $executorCallable = self::$dispatchTable[$class];

        // PHP-DI/Invokerで引数をtype-hintingで割り当てる
        $invoker = new Invoker(
            new ResolverChain([
                new TypeHintResolver(),
                new TypeHintContainerResolver($container)
            ]),
            $container);

        try {
            return $invoker->call($executorCallable,
                [
                    // TypeHintResolverで処理されるもの。これ以外はcontainerに問い合わされる
                    Pack::class => $requestObject,
                    ModelInterface::class => $request,
                    $class => $request
                ]);
        } catch (Exception $e) {
            throw new ModelException("Executor is not callable or failed to call", 0, $e);
        }
    }
}
