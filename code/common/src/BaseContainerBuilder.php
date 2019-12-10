<?php

namespace Gustav\Common;

use Aws\Sdk;
use Gustav\Common\Adapter\DynamoDbAdapter;
use Gustav\Common\Adapter\DynamoDbInterface;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\MySQLMasterInterface;
use Gustav\Common\Adapter\PgSQLAdapter;
use Gustav\Common\Adapter\PgSQLInterface;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Adapter\S3Adapter;
use Gustav\Common\Adapter\S3Interface;
use Gustav\Common\Adapter\SqsAdapter;
use Gustav\Common\Adapter\SqsInterface;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Log\DataLoggerFluent;
use Gustav\Common\Log\DataLoggerInterface;
use Gustav\Common\Log\DataLoggerSqs;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializer;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Gustav\Common\Model\Primitive\MessagePackSerializer;
use Gustav\Common\Network\NameResolver;
use Gustav\Common\Operation\BinaryEncryptor;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use PDO;
use DI\Container;
use DI\ContainerBuilder;
use Redis;


/**
 * Class BaseContainerBuilder
 * @package Gustav\Common
 */
class BaseContainerBuilder extends ContainerBuilder
{
    const DEFAULT_AWS_REGION = 'ap-northeast-1';

    /**
     * BaseContainerBuilder constructor.
     * @param ApplicationConfigInterface $config
     * @param string $containerClass
     */
    public function __construct(ApplicationConfigInterface $config, string $containerClass = Container::class)
    {
        parent::__construct($containerClass);

        $definitions = $this->getDefinitions($config);
        if (is_array($definitions) && !empty($definitions)) {
            $this->addDefinitions($definitions);
        }
    }

    /**
     * デフォルトの定義
     * @param ApplicationConfigInterface $config
     * @return array 定義
     */
    protected function getDefinitions(ApplicationConfigInterface $config): array
    {
        return [
            ApplicationConfigInterface::class  => $config,
            MySQLInterface::class => $this->getMySQLFunction(false),
            MySQLMasterInterface::class => $this->getMySQLFunction(true),
            PgSQLInterface::class => $this->getPgSQLFunction(),
            RedisInterface::class => $this->getRedisFunction(),
            DynamoDbInterface::class => $this->getDynamoDbFunction(),
            S3Interface::class => $this->getS3Function(),
            SqsInterface::class => $this->getSqsFunction(),
            BinaryEncryptorInterface::class => $this->getBinaryEncryptorFunction(),
            DataLoggerInterface::class => $this->getDataLoggerFunction(),
            DispatcherInterface::class => $this->getDispatcherFunction(),
            ModelSerializerInterface::class => $this->getModelSerializerFunction()
        ];
    }

    /**
     * PDO(MySQL)を取得するためのFunction
     * @param bool $forMaster
     * @return callable
     */
    protected function getMySQLFunction(bool $forMaster): callable
    {
        return function (ApplicationConfigInterface $config) use ($forMaster): MySQLInterface
        {
            $hostKey = $forMaster ? 'hostm' : 'host';
            list($host, $port) = $this->resolveHostAndPort($config->getValue('mysql', $hostKey));
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
                ),
                $forMaster
            );
        };
    }

    /**
     * PDO(PgSQL)を取得するためのFunction
     * @return callable
     */
    protected function getPgSQLFunction(): callable
    {
        return function (ApplicationConfigInterface $config): PgSQLInterface
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
        return function (ApplicationConfigInterface $config): RedisInterface
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
        return function (ApplicationConfigInterface $config): DynamoDbInterface
        {
            $sdk = new Sdk([
                'endpoint' => $config->getValue('dynamodb', 'endpoint'),
                'region' => $config->getValue('dynamodb', 'region', self::DEFAULT_AWS_REGION),
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
        return function (ApplicationConfigInterface $config): S3Interface
        {
            $sdk = new Sdk([
                'endpoint' => $config->getValue('storage', 'endpoint'),
                'region' => $config->getValue('storage', 'region', self::DEFAULT_AWS_REGION),
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
     * @return callable
     */
    protected function getSqsFunction(): callable
    {
        return function (ApplicationConfigInterface $config): SqsInterface
        {
            $sdk = new Sdk([
                'endpoint' => $config->getValue('sqs', 'endpoint'),
                'region' => $config->getValue('sqs', 'region', self::DEFAULT_AWS_REGION),
                'version' => '2012-11-05',
                'credentials' => [
                    'key' => $config->getValue('sqs', 'key'),
                    'secret' => $config->getValue('sqs', 'secret')
                ]
            ]);
            return new SqsAdapter($sdk->createSqs());
        };
    }

    /**
     * @return callable
     */
    protected function getBinaryEncryptorFunction(): callable
    {
        return function (): BinaryEncryptorInterface
        {
            return new BinaryEncryptor();
        };
    }

    /**
     * FluentLoggerを取得するためのFunction
     * @return callable
     */
    protected function getDataLoggerFunction(): callable
    {
        return function (ApplicationConfigInterface $config, Container $container): DataLoggerInterface
        {
            $loggerType = strtolower($config->getValue('logger', 'type'));

            if ($loggerType == 'fluent') {
                list($host, $port) = $this->resolveHostAndPort($config->getValue('logger', 'host'));
                return DataLoggerFluent::getInstance($host, $port);
            } elseif ($loggerType == 'sqs') {
                $sqsI = $container->get(SqsInterface::class);
                $queueUrl = $config->getValue('logger', 'queue');
                return DataLoggerSqs::getInstance($sqsI->getClient(), $queueUrl);
            }
            throw new ConfigException("logger.type is unknown type(${loggerType})");
        };
    }

    /**
     * Gustav\Common\DispatcherInterfaceを返すためのFunction
     * @return callable
     */
    protected function getDispatcherFunction(): callable
    {
        return function (): DispatcherInterface
        {
            return new BaseDispatcher();
        };
    }

    /**
     * Gustav\Common\ModelSerializerInterfaceを返すためのFunction
     * @return callable
     */
    protected function getModelSerializerFunction(): callable
    {
        return function (ApplicationConfigInterface $config): ModelSerializerInterface
        {
            $serializerType = strtolower($config->getValue('serializer', 'type'));

            if ($serializerType == 'flatbuffers') {
                return new FlatBuffersSerializer();
            } elseif ($serializerType == 'json') {
                return new JsonSerializer();
            } elseif ($serializerType == 'msgpack' || $serializerType == 'messagepack') {
                return new MessagePackSerializer();
            }
            throw new ConfigException("serializer.type is unknown type(${serializerType})");
        };
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
