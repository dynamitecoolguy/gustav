<?php

namespace Gustav\Common;

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
use Gustav\Common\Adapter\SqsAdapter;
use Gustav\Common\Adapter\SqsInterface;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Log\DataLoggerFactory;
use Gustav\Common\Log\DataLoggerInterface;
use Gustav\Common\Model\ModelSerializerFactory;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Network\AccessTokenManager;
use Gustav\Common\Network\AccessTokenManagerInterface;
use Gustav\Common\Network\BinaryEncryptor;
use Gustav\Common\Network\BinaryEncryptorInterface;
use DI\Container;
use DI\ContainerBuilder;
use Gustav\Common\Network\Dispatcher;
use Gustav\Common\Network\DispatcherInterface;
use Gustav\Common\Network\DispatcherTableInterface;
use Gustav\Common\Network\KeyOperator;
use Gustav\Common\Network\KeyOperatorInterface;
use function DI\create;
use function DI\factory;


/**
 * Class BaseContainerBuilder
 * @package Gustav\Common
 */
class BaseContainerBuilder extends ContainerBuilder
{
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

        $dispatcherTable = $this->getDispatcherTable();
        if (!is_null($dispatcherTable)) {
            $this->addDefinitions([
                DispatcherTableInterface::class => $dispatcherTable
            ]);
        }
    }

    /**
     * デフォルトの定義
     * @param ApplicationConfigInterface $config
     * @return array 定義
     * @uses \Gustav\Common\Model\ModelSerializerFactory::create()
     * @uses \Gustav\Common\Dispatcher::create()
     * @uses \Gustav\Common\Adapter\MySQLAdapter::create()
     * @uses \Gustav\Common\Adapter\PgSQLAdapter::create()
     * @uses \Gustav\Common\Adapter\RedisAdapter::create()
     * @uses \Gustav\Common\Adapter\DynamoDbAdapter::create()
     * @uses \Gustav\Common\Adapter\S3Adapter::create()
     * @uses \Gustav\Common\Adapter\SqsAdapter::create()
     * @uses \Gustav\Common\Log\DataLoggerFactory::create()
     */
    protected function getDefinitions(ApplicationConfigInterface $config): array
    {
        return [
            // 設定
            ApplicationConfigInterface::class  => $config,

            // 通信用加工処理 (暗号化、シリアライズ、ディスパッチ)
            BinaryEncryptorInterface::class => create(BinaryEncryptor::class),
            ModelSerializerInterface::class => factory([ModelSerializerFactory::class, 'create']),
            DispatcherInterface::class => factory([Dispatcher::class, 'create']),

            // AWSサービス
            MySQLInterface::class => factory([MySQLAdapter::class, 'create']),
            PgSQLInterface::class => factory([PgSQLAdapter::class, 'create']),
            RedisInterface::class => factory([RedisAdapter::class, 'create']),
            DynamoDbInterface::class => factory([DynamoDbAdapter::class, 'create']),
            S3Interface::class => factory([S3Adapter::class, 'create']),
            SqsInterface::class => factory([SqsAdapter::class, 'create']),

            // 鍵処理
            KeyOperatorInterface::class => create(KeyOperator::class),
            AccessTokenManagerInterface::class => create(AccessTokenManager::class),

            // データログ処理
            DataLoggerInterface::class => factory([DataLoggerFactory::class, 'create'])
        ];
    }

    /**
     * @return DispatcherTableInterface|null
     */
    protected function getDispatcherTable(): ?DispatcherTableInterface
    {
        return null;
    }
}
