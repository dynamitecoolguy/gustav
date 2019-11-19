<?php

use Slim\Middleware\ContentLengthMiddleware;
use DI\Bridge\Slim\Bridge;

use Gustav\App\Config\AppApplicationConfig as ApplicationConfig;
use Gustav\App\AppContainerBuilder as ContainerBuilder;
use Gustav\Common\Config\ConfigLoader;
use Gustav\Common\Config\SsmObjectMaker;
use Gustav\Common\Config\SsmObject;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../../vendor/autoload.php';
$loader->addPsr4('Gustav\\Mgr\\', __DIR__ . '/../src');               // mgr/src
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src

$ssmObjectMaker= SsmObjectMaker::getInstance(SsmObject::class, '/usr/local/etc/gustav/credentials/ssm');
$loader = ConfigLoader::getInstance('/usr/local/etc/gustav/settings.yml', $ssmObjectMaker);
$config = ApplicationConfig::getInstance($loader);

$containerBuilder = new ContainerBuilder($config);
$container = $containerBuilder->build();

$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

// Routing (@see PHP-DI in Slim)
$app->get('/hello/{who}', [Gustav\Mgr\Controller\HelloController::class, 'hello']);

$app->run();
