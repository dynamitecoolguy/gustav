<?php

use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\Mgr\MgrContainerBuilder as ContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Gustav\\Mgr\\', __DIR__ . '/../src');               // mgr/src
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src

$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml');
$config = new ApplicationConfig($loader);

$containerBuilder = new ContainerBuilder($config);
$container = $containerBuilder->build();

$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

$app->run();
