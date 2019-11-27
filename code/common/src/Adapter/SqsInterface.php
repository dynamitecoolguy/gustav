<?php


namespace Gustav\Common\Adapter;

use Aws\Sqs\SqsClient;

interface SqsInterface
{
    /**
     * @return SqsClient
     */
    public function getClient(): SqsClient;

}