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
     * @param ApplicationConfigInterface $config
     * @return DynamoDbAdapter
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): DynamoDbAdapter
    {
        $sdk = AwsSdkFactory::create($config, 'dynamodb', '2012-08-10');
        return new static($sdk->createDynamoDb());
    }

    /**
     * DynamoDbInterfaceをDynamoDbAdapterにwrapする
     * @param DynamoDbInterface $dynamoDb
     * @return DynamoDbAdapter
     */
    public static function wrap(DynamoDbInterface $dynamoDb): DynamoDbAdapter
    {
        return ($dynamoDb instanceof DynamoDbAdapter) ? $dynamoDb : new static($dynamoDb->getClient());
    }

    /**
     * DynamoDbAdapter constructor.
     * @param DynamoDbClient $client
     */
    public function __construct(DynamoDbClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return DynamoDbClient
     */
    public function getClient(): DynamoDbClient
    {
        return $this->client;
    }
}