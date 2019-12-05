<?php


namespace Gustav\Common\Config;


class LocalSsmObject implements SsmObjectInterface
{
    public function getParameters(array $keys): array
    {
        return [
            'MYSQL_USER' => 'scott',
            'MYSQL_PASSWORD' => 'tiger',
            'PGSQL_USER' => 'scott',
            'PGSQL_PASSWORD' => 'tiger',
            'DYNAMODB_ACCESS_KEY' => 'dummy',
            'DYNAMODB_SECRET' => 'dummy',
            'STORAGE_ACCESS_KEY' => 's3accesskey',
            'STORAGE_SECRET' => 's3secretkey',
            'STORAGE_BUCKET' => 'dummy'
        ];
    }

    /**
     * セットアップ用パラメータのセット
     * @param string[] $parameters
     */
    public function setUp(array $parameters): void
    {
        // do nothing
    }
}