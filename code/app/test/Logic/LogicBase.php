<?php


namespace Gustav\App\Logic;


use Composer\Autoload\ClassLoader;
use DI\Container;
use Gustav\App\AppContainerBuilder;
use Gustav\App\AppDispatcher;
use Gustav\App\LocalConfigLoader;
use Gustav\Common\Config\ApplicationConfig;
use PHPUnit\Framework\TestCase;

class LogicBase extends TestCase
{
    /** @var Container */
    public static $container;

    /** @var AppDispatcher */
    public static $dispatcher;

    /**
     * @beforeClass
     */
    public static function prepare(): void
    {
        $configLoader =  LocalConfigLoader::createConfigLoader();

        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../../src');               // app/src
        $autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../../common/src');  // common/src
        $autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../../flatbuffers/php');             // flatbuffers/php

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
}
