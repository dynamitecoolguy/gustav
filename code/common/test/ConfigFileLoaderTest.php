<?php


namespace Gustav\Common;


use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigFileLoaderTest extends TestCase
{
    private static $tempFilePath;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath = tempnam('/tmp', 'configfileloadertest');

        $fd = fopen(self::$tempFilePath, 'w');
        fwrite($fd, <<<'__EOF__'
userdb:
  host: mysql
  dbname: userdb
  user: scott
  password: tiger

logdb:
  host: psql
  dbname: logdb
  user: $$LOG_DB_USER$$
  password: $$LOG_DB_PASSWORD$$

redis:
  host: redis

dynamodb:
  endpoint: http://dynamodb:8000
  region: ap-northeast-1
  key: dummy
  secret: dummy
  table: hogehoge

storage:
  endpoint: http://storage:9000
  region: ap-northeast-1
  key: s3accesskey
  secret: s3secretkey
  bucket: dummy
__EOF__
        );
        fclose($fd);
    }

    /**
     * @afterClass
     */
    public static function afterClass(): void
    {
        unlink(self::$tempFilePath);
    }

    /**
     * @test
     */
    public function singleton(): void
    {
        $instanceA = ConfigFileLoader::getInstance('dummy');
        $instanceB = ConfigFileLoader::getInstance('dummy');
        ConfigFileLoader::resetInstance();

        $this->assertTrue($instanceA === $instanceB);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function getValidValue(): void
    {
        $loader = ConfigFileLoader::getInstance(self::$tempFilePath);
        $this->assertEquals($loader->getValue('dynamodb', 'table'), 'hogehoge');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidCategory(): void
    {
        $this->expectException(ConfigException::class);

        $loader = ConfigFileLoader::getInstance(self::$tempFilePath);
        $loader->getValue('no_such_category', 'table');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidKey(): void
    {
        $this->expectException(ConfigException::class);

        $loader = ConfigFileLoader::getInstance(self::$tempFilePath);
        $loader->getValue('dynamodb', 'no_such_key');
    }

    /**
     * @test
     */
    public function allVariables(): void
    {
        $loader = ConfigFileLoader::getInstance(self::$tempFilePath);
        $values = $loader->getAllVariables();
        sort($values);
        $this->assertSame($values, ['LOG_DB_PASSWORD', 'LOG_DB_USER']);
    }
}