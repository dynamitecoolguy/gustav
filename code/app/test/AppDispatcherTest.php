<?php


namespace Gustav\App;

use Gustav\App\Logic\UserRegistration;
use Gustav\App\Model\IdentificationModel;
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

        $this->assertEquals(UserRegistration::class, $dispatchTable[IdentificationModel::class]);
    }
}