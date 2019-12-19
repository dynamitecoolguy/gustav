<?php


namespace Gustav\Common\Network;

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
use Psr\Container\ContainerExceptionInterface;
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
     */
    public static function create(ContainerInterface $container): Dispatcher
    {
        return new static($container);
    }

    /**
     * Dispatcher constructor.
     * @param ContainerInterface $container
     * @throws ModelException
     */
    protected function __construct(ContainerInterface $container)
    {
        // Dispatcher用の定義ファイルの取得
        try {
            $dispatcherTableClass = $container->get(DispatcherTableInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new ModelException('DispatcherTableInterface is not registered or illegal', 0, $e);
        }
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
                throw new ModelException('Array size is too short');
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
            throw new ModelException('Executor is not registered');
        }

        // Executorを探す
        list($registeredClass, $executorCallable) = $this->dispatchTable[$packType];

        // 要求クラスと登録されているクラスが異なる
        if ($requestClass instanceof $registeredClass) {
            throw new ModelException('Request class is not match as registered class');
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
            throw new ModelException("Executor is not callable or failed to call", 0, $e);
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
