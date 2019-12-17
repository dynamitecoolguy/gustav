<?php


namespace Gustav\Common;


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

        BaseDispatcher::resetDispatchTable();
        $dispatcher = new DummyBaseDispatcher();
        $requestModel = new Pack('dummy', 1, 'req', $dummyModel);
        $resultModel = $dispatcher->dispatch($container, $requestModel);

        $this->assertEquals($resultModel, $dummyModel);
        $dispatchTable = DummyBaseDispatcher::getDispatchTable();
        $this->assertEquals(
            [DummyBaseDispatcherModel::class => DummyBaseDispatcherExecutor::class],
            $dispatchTable
        );
    }
}

class DummyBaseDispatcher extends BaseDispatcher
{
    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [['DUMMY', DummyBaseDispatcherModel::class, DummyBaseDispatcherExecutor::class]];
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