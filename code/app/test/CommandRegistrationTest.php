<?php


namespace Gustav\App;

use Composer\Autoload\ClassLoader;
use DI\Container;
use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Operation\Time;
use PHPUnit\Framework\TestCase;

class CommandRegistrationTest extends TestCase
{
    /** @var Container */
    private static $container;

    /** @var AppDispatcher */
    private static $dispatcher;

    /**
     * @beforeClass
     */
    public static function prepare(): void
    {
        $configLoader =  LocalConfigLoader::createConfigLoader();
        Time::now();

        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
        $autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
        $autoloader->addPsr4('Gustav\\DX\\', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php

        $config = new ApplicationConfig($configLoader);

        $containerBuilder = new AppContainerBuilder($config);
        self::$container = $containerBuilder->build();

        AppDispatcher::resetDispatchTable();
        self::$dispatcher = new AppDispatcher();
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
     * @throws ModelException
     */
    public function notRegistrationObject(): void
    {
        $this->expectException(ModelException::class);

        $request = new class implements ModelInterface {};
        self::$dispatcher->dispatch(1, self::$container, $request);
    }
    /**
     * @test
     * @throws ModelException
     */
    public function registration(): void
    {
        $request = new IdentificationModel([
            IdentificationModel::CAMPAIGN_CODE => 'hogehoge'
        ]);
        $result = self::$dispatcher->dispatch(1, self::$container, $request);

        $this->assertTrue(true);
    }

}