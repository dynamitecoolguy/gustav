<?php


namespace Gustav\Common\Config;


use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    private static $tempFilePath1;
    private static $tempFilePath2;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath1 = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath1, 'w');
        fwrite($fd, <<<'__EOF__'
mysql:
  host: mysql
  dbname: userdb
  user: !!This parameter is overwritten!!
  password: !!This parameter is overwritten!!

pgsql:
  host: pgsql
  dbname: logdb

redis:
  host: redis

dynamodb:
  endpoint: http://dynamodb:8000
  region: ap-northeast-1
  table: hogehoge

storage:
  endpoint: http://storage:9000
  region: ap-northeast-1
__EOF__
        );
        fclose($fd);

        self::$tempFilePath2 = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath2, 'w');
        fwrite($fd, <<<'__EOF__'
mysql:
  user: scott
  password: tiger

pgsql:
  user: scott
  password: tiger

dynamodb:
  key: dummy
  secret: dummy

storage:
  key: s3accesskey
  secret: s3secretkey
__EOF__
        );
        fclose($fd);
    }

    /**
     * @afterClass
     */
    public static function afterClass(): void
    {
        unlink(self::$tempFilePath2);
        unlink(self::$tempFilePath1);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function getValidValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath1, self::$tempFilePath2);
        $this->assertEquals($loader->getConfig('dynamodb', 'table', null), 'hogehoge');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidCategory(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath1, self::$tempFilePath2);
        $loader->getConfig('no_such_category', 'table', null);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidKey(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath1, self::$tempFilePath2);
        $loader->getConfig('dynamodb', 'no_such_key', null);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function defaultValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath1, self::$tempFilePath2);
        $this->assertEquals('default', $loader->getConfig('dynamodb', 'no_such_key_too', 'default'));
    }
}
