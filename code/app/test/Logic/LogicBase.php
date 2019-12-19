<?php


namespace Gustav\App\Logic;


use Composer\Autoload\ClassLoader;
use Gustav\App\AppContainerBuilder;
use Gustav\App\LocalConfigLoader;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Network\DispatcherInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LogicBase extends TestCase
{
    /** @var ContainerInterface */
    public static $container;

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
    }

    /**
     * @afterClass
     */
    public static function clean(): void
    {
        LocalConfigLoader::destroyConfigLoader();
    }

    /**
     * @return DispatcherInterface
     */
    public static function getDispatcher(): DispatcherInterface
    {
        return self::$container->get(DispatcherInterface::class);
    }
}
