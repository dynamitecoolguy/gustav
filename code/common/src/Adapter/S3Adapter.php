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
     * @param ApplicationConfigInterface $config
     * @return S3Adapter
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config): S3Adapter
    {
        $sdk = AwsSdkFactory::create(
            $config,
            'storage',
            '2006-03-01',
            ['use_path_style_endpoint' => true]
        );
        return new static($sdk->createS3());
    }

    /**
     * S3InterfaceをS3Adapterにwrapする
     * @param S3Interface $s3
     * @return S3Adapter
     */
    public static function wrap(S3Interface $s3): S3Adapter
    {
        return ($s3 instanceof S3Adapter) ? $s3 : new static($s3->getClient());
    }

    /**
     * S3Adapter constructor.
     * @param S3Client $client
     */
    public function __construct(S3Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return S3Client
     */
    public function getClient(): S3Client
    {
        return $this->client;
    }
}