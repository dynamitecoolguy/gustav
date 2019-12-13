<?php


namespace Gustav\Common\Adapter;


use Aws\S3\S3Client;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;

/**
 * Class S3Adapter
 * @package Gustav\Common\Adapter
 */
class S3Adapter implements S3Interface
{
    /**
     * @var S3Client
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
            'storage',
            '2006-03-01',
            ['use_path_style_endpoint' => true]
        );
        $this->client = $sdk->createS3();
    }

    /**
     * @return S3Client
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
}