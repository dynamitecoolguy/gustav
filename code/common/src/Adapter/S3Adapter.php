<?php


namespace Gustav\Common\Adapter;


use Aws\S3\S3Client;

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