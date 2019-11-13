<?php

$loader = require __DIR__ . '/code/vendor/autoload.php';
$loader->addPsr4('Gustav\\App\\', __DIR__ . '/code/app/src');
$loader->addPsr4('Gustav\\App\\', __DIR__ . '/code/app/test');
$loader->addPsr4('Gustav\\Mgr\\', __DIR__ . '/code/mgr/src');
$loader->addPsr4('Gustav\\Mgr\\', __DIR__ . '/code/mgr/test');
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/code/common/src');
$loader->addPsr4('Gustav\\Common\\', __DIR__ . '/code/common/test');

return $loader;
