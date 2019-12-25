<?php


namespace Gustav\Common\Network;


use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\NetworkException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class BaseDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function dispatch(): void
    {
        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class]];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $dummyModel = new DummyBaseDispatcherModel();
        $requestModel = new Pack('DUMMY', 1, 'req', $dummyModel);
        $resultModel = $dispatcher->dispatch($container, $requestModel);

        $this->assertEquals($resultModel, $dummyModel);
        $dispatchTable = $dispatcher->getDispatchTable();
        $this->assertEquals(
            ['DUMMY' => [DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class, true]],
            $dispatchTable
        );
    }

    /**
     * @test
     */
    public function noDispatcher(): void
    {
        $this->expectException(NetworkException::class);

        // getContainer
        $container = $this->createContainer();
        Dispatcher::create($container);
    }

    /**
     * @test
     */
    public function illegalDispatcherTable(): void
    {
        $this->expectException(NetworkException::class);

        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class]];
                }
            }
        );

        Dispatcher::create($container);
    }

    /**
     * @test
     */
    public function undefinedPack(): void
    {
        $this->expectException(NetworkException::class);

        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class]];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $dummyModel = new DummyBaseDispatcherModel();
        $requestModel = new Pack('HOGE', 1, 'req', $dummyModel);
        $dispatcher->dispatch($container, $requestModel);
    }

    /**
     * @test
     */
    public function invalidReturnModel(): void
    {
        $this->expectException(NetworkException::class);

        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class]];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $requestModel = new Pack('DUMMY', 1, 'req',
            new class implements ModelInterface {}
        );
        $dispatcher->dispatch($container, $requestModel);
    }

    /**
     * @test
     */
    public function exceptionOccurred(): void
    {
        $this->expectException(NetworkException::class);

        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class, [DummyBaseDispatcherExecutor::class, 'error']]];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $dummyModel = new DummyBaseDispatcherModel();
        $requestModel = new Pack('DUMMY', 1, 'req', $dummyModel);
        $dispatcher->dispatch($container, $requestModel);
    }

    /**
     * @test
     */
    public function checkToken(): void
    {
        // getContainer
        $container = $this->createContainer();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [
                        ['DUMMY1', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class],
                        ['DUMMY2', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class, true],
                        ['DUMMY3', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class, false]
                    ];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $dummyModel = new DummyBaseDispatcherModel();
        $requestModel1 = new Pack('DUMMY1', 1, 'req', $dummyModel);
        $requestModel2 = new Pack('DUMMY2', 1, 'req', $dummyModel);
        $requestModel3 = new Pack('DUMMY3', 1, 'req', $dummyModel);

        $this->assertTrue($dispatcher->isTokenRequired($requestModel1));
        $this->assertTrue($dispatcher->isTokenRequired($requestModel2));
        $this->assertFalse($dispatcher->isTokenRequired($requestModel3));
    }

    private function createContainer(): ContainerInterface
    {
        // getContainer
        $builder = new BaseContainerBuilder(
            new class implements ApplicationConfigInterface {
                public function getValue(string $category, string $key, ?string $default = null): string
                {
                    return 'dummy';
                }
            }
        );
        return $builder->build();
    }
}

class DummyBaseDispatcherModel implements ModelInterface
{
}

class DummyBaseDispatcherExecutor
{
    public function __invoke(DummyBaseDispatcherModel $model): ?ModelInterface
    {
        return $model;
    }

    public function error(): ?ModelInterface
    {
        throw new \Exception();
    }
}