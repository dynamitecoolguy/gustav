<?php

namespace Gustav\Common;

use Aws\Sdk;
use PDO;
use Psr\Container\ContainerInterface;
use DI\Container;
use DI\ContainerBuilder;
use Redis;

use Gustav\Common\Network\NameResolver;
use Gustav\Common\Config\BaseApplicationConfig;

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
                list($host, $port) = $this->resolveHostAndPort($config->getValue('mysql', 'host'));
                $dsn = 'mysql:host=' . $host . ';dbname=' . $config->getValue('mysql', 'dbname');
                if ($port !== false) {
                    $dsn .= ';port=' . $port;
                }
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
                list($host, $port) = $this->resolveHostAndPort($config->getValue('pgsql', 'host'));
                $dsn = 'pgsql:host=' . $host . ';dbname=' . $config->getValue('pgsql', 'dbname');
                if ($port !== false) {
                    $dsn .= ';port=' . $port;
                }
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
                list($host, $port) = $this->resolveHostAndPort($config->getValue('redis', 'host'));
                $redis = new Redis();
                if ($port !== false) {
                    $redis->connect($host, $port);
                } else {
                    $redis->connect($host);
                }
                $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

                return $redis;
            },
            'dynamodb' => function (ContainerInterface $container) use($config)
            {
                $sdk = new Sdk([
                    'endpoint' => $this->resolveEndpoint($config->getValue('dynamodb', 'endpoint')),
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
                    'endpoint' => $this->resolveEndpoint($config->getValue('storage', 'endpoint')),
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

    /**
     * Endpointの名前を解決
     * @param string $endpoint
     * @return string
     */
    private function resolveEndpoint($endpoint): string
    {
        $protocolPos = strpos($endpoint, ':');
        $protocol = substr($endpoint, 0, $protocolPos + 3);
        $hostAndPort = substr($endpoint, $protocolPos + 3);
        list($host, $port) = $this->resolveHostAndPort($hostAndPort);
        if ($port !== false) {
            $endpoint = $protocol . $host . ':' . $port;
        } else {
            $endpoint = $protocol . $host;
        }
        return $endpoint;
    }

    /**
     * ホスト名からIPとポートを分離する
     * @param $host
     * @return array  [string, integer|false] ホスト名, ポート番号
     */
    private function resolveHostAndPort($host): array
    {
        $portPos = strrpos($host, ':');
        if ($portPos === false) {
            return [NameResolver::getIp($host), false];
        }
        $hostBody = substr($host, 0, $portPos);
        $port = substr($host, $portPos + 1);
        return [NameResolver::getIp($hostBody), intval($port)];
    }
}
