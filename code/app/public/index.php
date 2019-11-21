<?php

use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\App\AppContainerBuilder as ContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;
use Gustav\Common\Config\SsmObject;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src

$loader = new ConfigLoader(
    '/usr/local/etc/gustav/settings.yml',
    SsmObject::class,
    [
        SsmObject::KEY_ACCOUNT_FILE => '/usr/local/etc/gustav/credentials/ssm'
    ]
);
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
