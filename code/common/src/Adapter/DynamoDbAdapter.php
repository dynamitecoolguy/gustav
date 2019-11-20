<?php


namespace Gustav\Common\Adapter;


use Aws\DynamoDb\DynamoDbClient;

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