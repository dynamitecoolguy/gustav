<?php


namespace Gustav\Common\Adapter;

use Aws\S3\S3Client;

/**
 * Interface S3Interface
 * @package Gustav\Common\Adapter
 */
interface S3Interface
{
    /**
     * @return S3Client
     */
    public function getClient(): S3Client;
}
