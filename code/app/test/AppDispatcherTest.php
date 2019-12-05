<?php


namespace Gustav\App;

use Gustav\App\Logic\RegistrationExecutor;
use Gustav\App\Model\RegistrationModel;
use PHPUnit\Framework\TestCase;

class AppDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function getModel()
    {
        AppDispatcher::resetDispatchTable();
        $dispatcher = new AppDispatcher();

        $dispatchTable = $dispatcher->getDispatchTable();

        $this->assertEquals(RegistrationExecutor::class, $dispatchTable[RegistrationModel::class]);
    }
}