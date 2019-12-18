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
     * @param ApplicationConfigInterface $config
     * @return SqsAdapter
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): SqsAdapter
    {
        $sdk = AwsSdkFactory::create(
            $config,
            'sqs',
            '2012-11-05'
        );
        return new static($sdk->createSqs());
    }

    /**
     * SqsInterfaceをSqsAdapterにwrapする
     * @param SqsInterface $sqs
     * @return SqsAdapter
     */
    public static function wrap(SqsInterface $sqs): SqsAdapter
    {
        return ($sqs instanceof SqsAdapter) ? $sqs : new static($sqs->getClient());
    }

    /**
     * SqsAdapter constructor.
     * @param SqsClient $client
     */
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
