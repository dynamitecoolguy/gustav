<?php

$loader = require '/var/www/vendor/autoload.php';
$loader->addPsr4('Gustav\\App\\', '/var/www/app/src');
$loader->addPsr4('Gustav\\App\\', '/var/www/app/test');
$loader->addPsr4('Gustav\\Common\\', '/var/www/common/src');
$loader->addPsr4('Gustav\\Common\\', '/var/www/common/test');
