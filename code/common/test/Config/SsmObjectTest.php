<?php


namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class SsmObjectTest extends TestCase
{
    /**
     * @test
     * @group use_aws
     * @throws ConfigException
     */
    public function createSsmObject(): void
    {
        $ssmObject = new SsmObject();
        $ssmObject->setUp([
            SsmObject::KEY_ACCOUNT_FILE => __DIR__ . '/../../../../credentials/ssm',
            SsmObject::KEY_REGION => SsmObject::DEFAULT_REGION,
            SsmObject::KEY_PROFILE => SsmObject::DEFAULT_PROFILE
        ]);

        $keys = [
            'DYNAMODB_ACCESS_KEY',
            'DYNAMODB_SECRET',
            'PGSQL_PASSWORD',
            'PGSQL_USER',
            'STORAGE_ACCESS_KEY',
            'STORAGE_BUCKET',
            'STORAGE_SECRET',
            'MYSQL_PASSWORD',
            'MYSQL_USER'
        ];

        $values = $ssmObject->getParameters($keys);

        $this->assertEquals(9, sizeof($values));
    }
}