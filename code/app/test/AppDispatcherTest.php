<?php


namespace Gustav\App;

use Composer\Autoload\ClassLoader;
use DI\Container;
use Gustav\App\Logic\UserRegistration;
use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;
use PHPUnit\Framework\TestCase;

class AppDispatcherTest extends TestCase
{
    /** @var Container */
    private static $container;

    /**
     * @beforeClass
     */
    public static function prepare(): void
    {
        $configLoader =  LocalConfigLoader::createConfigLoader();

        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
        $autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
        $autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php

        $config = new ApplicationConfig($configLoader);

        $containerBuilder = new AppContainerBuilder($config);
        self::$container = $containerBuilder->build();
    }

    /**
     * @afterClass
     */
    public static function clean(): void
    {
        LocalConfigLoader::destroyConfigLoader();
    }

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

    /**
     * @test
     * @throws ModelException
     */
    public function notRegistrationObject(): void
    {
        $this->expectException(ModelException::class);

        AppDispatcher::resetDispatchTable();
        $dispatcher = new AppDispatcher();
        $request = new class implements ModelInterface {};
        $requestObject = new ModelChunk('MON', 1, 'req1', $request);
        $dispatcher->dispatch(self::$container, $requestObject);
    }
}