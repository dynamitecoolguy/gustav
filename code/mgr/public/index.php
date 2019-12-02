<?php

use Gustav\Common\Operation\Time;
use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\Mgr\MgrContainerBuilder as ContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../../vendor/autoload.php';
$autoloader->addPsr4('Gustav\\Mgr\\', __DIR__ . '/../src');               // mgr/src
$autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
$autoloader->addPsr4('', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php

// Set currentTime
Time::now();

$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml');
$config = new ApplicationConfig($loader);

$containerBuilder = new ContainerBuilder($config);
$container = $containerBuilder->build();

$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

$app->run();
