<?php

use DI\Bridge\Slim\Bridge;
use Gustav\App\AppContainerBuilder;
use Gustav\App\Controller\MainController;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;
use Gustav\Common\Operation\Time;
use Slim\Middleware\ContentLengthMiddleware;

/** @var Composer\Autoload\ClassLoader $autoloader */
$autoloader = require __DIR__ . '/../../vendor/autoload.php';
$autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../src');               // app/src
$autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../common/src');  // common/src
$autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../flatbuffers/php');             // flatbuffers/php

// Set currentTime
Time::now();

// 設定ファイルの読み込み
$loader = new ConfigLoader('/usr/local/etc/gustav/settings.yml', '/usr/local/etc/gustav/settings-secret.yml');

// 設定取得用クラスの作成
$config = new ApplicationConfig($loader);

// DIコンテナの作成
$containerBuilder = new AppContainerBuilder($config);
$container = $containerBuilder->build();

// SLIMアプリケーションの作成
$app = Bridge::create($container);

// Middleware
$app->add(new ContentLengthMiddleware());

// ルーティング (@see PHP-DI in Slim)
if ($config->getValue('app', 'debugapi', 'false')) {
    // デバグ/テスト用途
    $app->get('/hello/{who}', [Gustav\App\Controller\HelloController::class, 'hello']);
    $app->get('/mysql/{number}', [Gustav\App\Controller\HelloController::class, 'mysql']);
    $app->get('/pgsql', [Gustav\App\Controller\HelloController::class, 'pgsql']);
    $app->get('/redis', [Gustav\App\Controller\HelloController::class, 'redis']);
    $app->get('/dynamo', [Gustav\App\Controller\HelloController::class, 'dynamo']);
    $app->get('/s3/{from}/{to}', [Gustav\App\Controller\HelloController::class, 's3']);
}
/** @uses MainController::post() */
$app->post('/', [MainController::class, 'post']);

// 実行
$app->run();
