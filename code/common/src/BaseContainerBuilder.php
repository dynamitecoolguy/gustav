<?php

namespace Gustav\Common;

use Aws\Sdk;
use PDO;
use Psr\Container\ContainerInterface;
use DI\Container;
use DI\ContainerBuilder;
use Redis;

/**
 * Class BaseContainerBuilder
 * @package Gustav\Common
 */
class BaseContainerBuilder extends ContainerBuilder
{
    /**
     * BaseContainerBuilder constructor.
     * @param BaseApplicationConfig $config
     * @param string $containerClass
     */
    public function __construct(BaseApplicationConfig $config, string $containerClass = Container::class)
    {
        parent::__construct($containerClass);

        $definitions = $this->getDefinitions($config);
        if (is_array($definitions) && !empty($definitions)) {
            $this->addDefinitions($definitions);
        }
    }

    /**
     * デフォルトの定義
     * @param BaseApplicationConfig $config
     * @return array 定義
     */
    protected function getDefinitions(BaseApplicationConfig $config): array
    {
        return [
            'mysql' => function (ContainerInterface $container) use($config)
            {
                $dsn = 'mysql:host=' . $config->getValue('mysql', 'host')
                    . ';dbname=' . $config->getValue('mysql', 'dbname');
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                return new PDO(
                    $dsn,
                    $config->getValue('mysql', 'user'),
                    $config->getValue('mysql', 'password'),
                    $options
                );
            },
            'pgsql' => function (ContainerInterface $container) use($config)
            {
                $dsn = 'pgsql:host=' . $config->getValue('pgsql', 'host')
                    . ';dbname=' . $config->getValue('pgsql', 'dbname');
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                return new PDO(
                    $dsn,
                    $config->getValue('pgsql', 'user'),
                    $config->getValue('pgsql', 'password'),
                    $options
                );
            },
            'redis' => function (ContainerInterface $container) use($config)
            {
                $redis = new Redis();
                $redis->connect($config->getValue('redis', 'host'));
                $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

                return $redis;
            },
            'dynamodb' => function (ContainerInterface $container) use($config)
            {
                $sdk = new Sdk([
                    'endpoint' => $config->getValue('dynamodb', 'endpoint'),
                    'region' => $config->getValue('dynamodb', 'region'),
                    'version' => '2012-08-10',
                    'credentials' => [
                        'key' => $config->getValue('dynamodb', 'key'),
                        'secret' => $config->getValue('dynamodb', 'secret')
                    ]
                ]);

                return $sdk->createDynamoDb();
            },
            'storage' => function (ContainerInterface $container) use($config)
            {
                $sdk = new Sdk([
                    'endpoint' => $config->getValue('storage', 'endpoint'),
                    'region' => $config->getValue('storage', 'region'),
                    'version' => '2006-03-01',
                    'credentials' => [
                        'key' => $config->getValue('storage', 'key'),
                        'secret' => $config->getValue('storage', 'secret')
                    ],
                    'use_path_style_endpoint' => true
                ]);
                return $sdk->createS3();
            }
        ];
    }
}
