<?php

namespace Gustav\Common;

use Aws\Sdk;
use Gustav\Common\Adapter\DynamoDbAdapter;
use Gustav\Common\Adapter\DynamoDbInterface;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\PgSQLAdapter;
use Gustav\Common\Adapter\PgSQLInterface;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Adapter\S3Adapter;
use Gustav\Common\Adapter\S3Interface;
use Gustav\Common\Config\ApplicationConfigInterface;
use PDO;
use Psr\Container\ContainerInterface;
use DI\Container;
use DI\ContainerBuilder;
use Redis;

use Gustav\Common\Network\NameResolver;
use Gustav\Common\Config\ApplicationConfig;
use Slim\App;

/**
 * Class BaseContainerBuilder
 * @package Gustav\Common
 */
class BaseContainerBuilder extends ContainerBuilder
{
    /**
     * BaseContainerBuilder constructor.
     * @param ApplicationConfig $config
     * @param string $containerClass
     */
    public function __construct(ApplicationConfig $config, string $containerClass = Container::class)
    {
        parent::__construct($containerClass);

        $definitions = $this->getDefinitions($config);
        if (is_array($definitions) && !empty($definitions)) {
            $this->addDefinitions($definitions);
        }
    }

    /**
     * デフォルトの定義
     * @param ApplicationConfig $config
     * @return array 定義
     */
    protected function getDefinitions(ApplicationConfig $config): array
    {
        return [
            ApplicationConfigInterface::class  => $config,
            MySQLInterface::class => $this->getMySQLFunction(),
            PgSQLInterface::class => $this->getPgSQLFunction(),
            RedisInterface::class => $this->getRedisFunction(),
            DynamoDbInterface::class => $this->getDynamoDbFunction(),
            S3Interface::class => $this->getS3Function()
        ];
    }

    /**
     * PDO(MySQL)を取得するためのFunction
     * @return callable
     */
    protected function getMySQLFunction(): callable
    {
        return function (ApplicationConfigInterface $config): MySQLAdapter
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
            return new MySQLAdapter(
                new PDO(
                    $dsn,
                    $config->getValue('mysql', 'user'),
                    $config->getValue('mysql', 'password'),
                    $options
                )
            );
        };
    }

    /**
     * PDO(PostgreSQL)を取得するためのFunction
     * @return callable
     */
    protected function getPgSQLFunction(): callable
    {
        return function (ContainerInterface $container, ApplicationConfigInterface $config): PgSQLAdapter
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
            return new PgSQLAdapter(
                new PDO(
                    $dsn,
                    $config->getValue('pgsql', 'user'),
                    $config->getValue('pgsql', 'password'),
                    $options
                )
            );
        };
    }

    /**
     * Redisオブジェクトを取得
     * @return callable
     */
    protected function getRedisFunction(): callable
    {
        return function (ContainerInterface $container, ApplicationConfigInterface $config): RedisAdapter
        {
            list($host, $port) = $this->resolveHostAndPort($config->getValue('redis', 'host'));
            $redis = new Redis();
            if ($port !== false) {
                $redis->connect($host, $port);
            } else {
                $redis->connect($host);
            }
            $redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);

            return new RedisAdapter($redis);
        };
    }

    /**
     * @return callable
     */
    protected function getDynamoDbFunction(): callable
    {
        return function (ContainerInterface $container, ApplicationConfigInterface $config): DynamoDbAdapter
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
            return new DynamoDbAdapter($sdk->createDynamoDb());
        };
    }

    /**
     * @return callable
     */
    protected function getS3Function(): callable
    {
        return function (ContainerInterface $container, ApplicationConfigInterface $config): S3Adapter
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
            return new S3Adapter($sdk->createS3());
        };
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
