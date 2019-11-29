<?php

use Gustav\Common\Operation\Time;
use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\App\AppContainerBuilder as ContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../../vendor/autoload.php';
$autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
$autoloader->addPsr4('', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/src

// Set currentTime
Time::now();

$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml');
$config = new ApplicationConfig($loader);

$containerBuilder = new ContainerBuilder($config);
$container = $containerBuilder->build();

$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

// Routing (@see PHP-DI in Slim)
$app->get('/hello/{who}', [Gustav\App\Controller\HelloController::class, 'hello']);
$app->get('/mysql/{number}', [Gustav\App\Controller\HelloController::class, 'mysql']);
$app->get('/pgsql', [Gustav\App\Controller\HelloController::class, 'pgsql']);
$app->get('/redis', [Gustav\App\Controller\HelloController::class, 'redis']);
$app->get('/dynamo', [Gustav\App\Controller\HelloController::class, 'dynamo']);
$app->get('/s3/{from}/{to}', [Gustav\App\Controller\HelloController::class, 's3']);

$app->run();
