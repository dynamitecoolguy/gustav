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
        try {
            $dispatcherTableClass = $container->get(DispatcherTableInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new ModelException('DispatcherTableInterface is not registered or illegal', 0, $e);
        }
        $dispatcherList = $dispatcherTableClass->getDispatchTable();

        $this->dispatchTable = [];
        foreach ($dispatcherList as $record) {
            list($packType, $modelClass, $executorCallable) = $record;

            ModelMapper::registerModel($packType, $modelClass);

            $this->dispatchTable[$modelClass] = $executorCallable;
        }
    }

    /**
     * @param ContainerInterface $container
     * @param Pack $requestObject
     * @return ModelInterface|null
     * @throws GustavException
     */
    public function dispatch(ContainerInterface $container, Pack $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();

        $class = get_class($request);
        if (!isset($this->dispatchTable[$class])) {
            throw new ModelException('Executor is not registered');
        }

        // Executorを探す
        $executorCallable = $this->dispatchTable[$class];

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

    /**
     * @return array
     */
    public function getDispatchTable(): array
    {
        return $this->dispatchTable;
    }
}
