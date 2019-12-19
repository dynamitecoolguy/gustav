<?php


namespace Gustav\Common\Network;


use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

class BaseDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function dispatch(): void
    {
        $dummyModel = new DummyBaseDispatcherModel();
        // getContainer
        $builder = new BaseContainerBuilder(
            new class implements ApplicationConfigInterface {
                public function getValue(string $category, string $key, ?string $default = null): string
                {
                    return 'dummy';
                }
            }
        );
        $container = $builder->build();
        $container->set(DispatcherTableInterface::class,
            new class implements DispatcherTableInterface {
                public function getDispatchTable(): array
                {
                    return [['DUMMY', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class]];
                }
            }
        );

        $dispatcher = Dispatcher::create($container);
        $requestModel = new Pack('DUMMY', 1, 'req', $dummyModel);
        $resultModel = $dispatcher->dispatch($container, $requestModel);

        $this->assertEquals($resultModel, $dummyModel);
        $dispatchTable = $dispatcher->getDispatchTable();
        $this->assertEquals(
            ['DUMMY' => [DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class, true]],
            $dispatchTable
        );
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
}