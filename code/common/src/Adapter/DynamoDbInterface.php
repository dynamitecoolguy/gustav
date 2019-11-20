<?php


namespace Gustav\Common\Adapter;

use Aws\DynamoDb\DynamoDbClient;

/**
 * Interface DynamoDbInterface
 * @package Gustav\Common\Adapter
 */
interface DynamoDbInterface
{
    /**
     * @return DynamoDbClient
     */
    public function getClient(): DynamoDbClient;
}