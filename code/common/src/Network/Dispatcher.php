<?php


namespace Gustav\Common\Network;

use Exception;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Exception\NetworkException;
use Invoker\Invoker;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelInterface;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Invoker\ParameterResolver\TypeHintResolver;
use Psr\Container\ContainerInterface;

/**
 * Class Dispatcher
 * @package Gustav\Common
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @var array
     */
    private $dispatchTable;

    /**
     * Dispatcherの作成
     * @param ContainerInterface $container
     * @return Dispatcher
     * @throws ModelException
     * @throws NetworkException
     */
    public static function create(ContainerInterface $container): Dispatcher
    {
        return new static($container);
    }

    /**
     * Dispatcher constructor.
     * @param ContainerInterface $container
     * @throws NetworkException
     * @throws ModelException
     */
    protected function __construct(ContainerInterface $container)
    {
        // Dispatcher用の定義ファイルの取得
        if (!$container->has(DispatcherTableInterface::class)) {
            throw new NetworkException(
                NetworkException::DISPATCHER_TABLE_INTERFACE_IS_NOT_REGISTERED
            );
        }

        $dispatcherTableClass = $container->get(DispatcherTableInterface::class);
        $dispatcherList = $dispatcherTableClass->getDispatchTable();

        $this->dispatchTable = [];
        foreach ($dispatcherList as $record) {
            $length = sizeof($record);
            if ($length == 3) {
                list($packType, $modelClass, $executorCallable) = $record;
                $tokenRequired = true;
            } elseif ($length > 3) {
                list($packType, $modelClass, $executorCallable, $tokenRequired) = $record;
            } else {
                throw new NetworkException(
                    'Dispatch table has illegal record',
                    NetworkException::DISPATCHER_TABLE_HAS_ILLEGAL_RECORD
                );
            }

            ModelMapper::registerModel($packType, $modelClass);

            $this->dispatchTable[$packType] = [$modelClass, $executorCallable, $tokenRequired];
        }
    }

    /**
     * @param ContainerInterface   $container
     * @param Pack                 $requestPack
     * @return ModelInterface|null
     * @throws GustavException
     */
    public function dispatch(ContainerInterface $container, Pack $requestPack): ?ModelInterface
    {
        $packType = $requestPack->getPackType();
        $requestModel = $requestPack->getModel();
        $requestClass = get_class($requestModel);

        if (!isset($this->dispatchTable[$packType])) {
            throw new NetworkException(
                "Executor is not registered for packType(${packType})",
                NetworkException::EXECUTOR_IS_NOT_REGISTERED
            );
        }

        // Executorを探す
        list($registeredClass, $executorCallable) = $this->dispatchTable[$packType];

        // 要求クラスと登録されているクラスが異なる
        if (!($requestModel instanceof $registeredClass)) {
            throw new NetworkException(
                "Requested class(${requestClass}) is not same as registered class(${registeredClass})",
                NetworkException::CLASSES_IS_NOT_SAME
            );
        }

        // PHP-DI/Invokerで引数をtype-hintingで割り当てる
        $invoker = new Invoker(
            new ResolverChain([
                new TypeHintResolver(),
                new TypeHintContainerResolver($container)
            ]),
            $container);

        // Executorを実行する
        try {
            return $invoker->call($executorCallable,
                [
                    // TypeHintResolverで処理されるもの。これ以外はcontainerに問い合わされる
                    Pack::class              => $requestPack,
                    ModelInterface::class    => $requestModel,
                    $requestClass            => $requestModel
                ]);
        } catch (Exception $e) {
            throw new NetworkException(
                "Executor is not callable or failed to call",
                NetworkException::EXECUTOR_HAS_EXCEPTION,
                $e
            );
        }
    }

    /**
     * トークンが必要かどうか
     * @param Pack $requestToken
     * @return bool
     */
    public function isTokenRequired(Pack $requestToken): bool
    {
        return $this->dispatchTable[$requestToken->getPackType()][2] ?? false;
    }

    /**
     * @return array  PackTypeをキーに(モデルクラス, 実行callable, Token必要かどうか?)のマップを返す
     */
    public function getDispatchTable(): array
    {
        return $this->dispatchTable;
    }
}
