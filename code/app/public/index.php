<?php

use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\App\AppApplicationConfig as ApplicationConfig;
use Gustav\App\AppContainerBuilder as ContainerBuilder;
use Gustav\Common\ConfigFileLoader;
use Gustav\Common\SsmLoader;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src

$config = ApplicationConfig::getInstance(
    ConfigFileLoader::getInstance('/usr/local/etc/gustav/settings.yml'),
    SsmLoader::getInstance('/usr/local/etc/gustav/credentials/ssm')
);
$containerBuilder = new ContainerBuilder($config);
$container = $containerBuilder->build();

$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

// Routing (@see PHP-DI in Slim)
$app->get('/hello/{who}', [Gustav\App\Controller\HelloController::class, 'hello']);

$app->run();
