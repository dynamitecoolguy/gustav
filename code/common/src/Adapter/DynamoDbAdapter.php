<?php


namespace Gustav\Common\Adapter;


use Aws\DynamoDb\DynamoDbClient;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;

/**
 * Class DynamoDbAdapter
 * @package Gustav\Common\Adapter
 */
class DynamoDbAdapter implements DynamoDbInterface
{
    /**
     * @var DynamoDbClient
     */
    private $client;

    /**
     * DynamoDbAdapter constructor.
     * @param ApplicationConfigInterface $config
     * @throws ConfigException
     */
    public function __construct(ApplicationConfigInterface $config)
    {
        $sdk = AwsSdkFactory::create($config, 'dynamodb', '2012-08-10');
        $this->client = $sdk->createDynamoDb();
    }

    /**
     * @return DynamoDbClient
     */
    public function getClient(): DynamoDbClient
    {
        return $this->client;
    }
}