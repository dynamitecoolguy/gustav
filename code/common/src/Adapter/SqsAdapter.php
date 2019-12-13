<?php


namespace Gustav\Common\Adapter;


use Aws\Sqs\SqsClient;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;

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

    /**
     * S3Adapter constructor.
     * @param ApplicationConfigInterface $config
     * @throws ConfigException
     */
    public function __construct(ApplicationConfigInterface $config)
    {
        $sdk = AwsSdkFactory::create(
            $config,
            'sqs',
            '2012-11-05'
        );
        $this->client = $sdk->createSqs();
    }

    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient
    {
        return $this->client;
    }
}
