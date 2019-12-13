<?php

namespace Gustav\Common;

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
use Gustav\Common\Log\DataLoggerFactory;
use Gustav\Common\Log\DataLoggerInterface;
use Gustav\Common\Model\ModelSerializerFactory;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Operation\BinaryEncryptor;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use DI\Container;
use DI\ContainerBuilder;
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
            MySQLInterface::class => create(MySQLAdapter::class)->constructor($config, false),
            MySQLMasterInterface::class => create(MySQLAdapter::class)->constructor($config, true),
            PgSQLInterface::class => create(PgSQLAdapter::class)->constructor($config),
            RedisInterface::class => create(RedisAdapter::class)->constructor($config),
            DynamoDbInterface::class => create(DynamoDbAdapter::class)->constructor($config),
            S3Interface::class => create(S3Adapter::class)->constructor($config),
            SqsInterface::class => create(SqsAdapter::class)->constructor($config),
            BinaryEncryptorInterface::class => create(BinaryEncryptor::class),
            DataLoggerInterface::class => factory([DataLoggerFactory::class, 'create']),
            DispatcherInterface::class => create(BaseDispatcher::class),
            ModelSerializerInterface::class => factory([ModelSerializerFactory::class, 'create'])
        ];
    }
}
