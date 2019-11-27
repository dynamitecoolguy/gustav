<?php


namespace Gustav\Common\Adapter;


use Aws\Sqs\SqsClient;

/**
 * Class SqsAdapter
 * @package Gustav\Common\Adapter
 */
class SqsAdapter implements SqsInterface
{
    /**
     * @var SqsClient
     */
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient
    {
        return $this->client;
    }
}
