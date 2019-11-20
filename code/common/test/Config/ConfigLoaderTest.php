<?php


namespace Gustav\Common\Config;


use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{
    private static $tempFilePath;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath = tempnam('/tmp', 'configloadertest');

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
     * @throws ConfigException
     */
    public function getValidValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);
        $this->assertEquals($loader->getConfig('dynamodb', 'table'), 'hogehoge');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidCategory(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);
        $loader->getConfig('no_such_category', 'table');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidKey(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);
        $loader->getConfig('dynamodb', 'no_such_key');
    }

    /**
     * @test
     */
    public function allVariables(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);
        $values = $loader->getAllVariables();
        sort($values);
        $this->assertSame($values, ['LOG_DB_PASSWORD', 'LOG_DB_USER']);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function replaceValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);

        $this->assertEquals('LOG_DB_USER_VALUE', $loader->replaceVariable('LOG_DB_USER'));
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $loader->replaceVariable('LOG_DB_PASSWORD'));

        // cache used
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $loader->replaceVariable('LOG_DB_PASSWORD'));

        // apc used
        $anotherLoader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $anotherLoader->replaceVariable('LOG_DB_PASSWORD'));
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function replaceValueFailure(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);

        $loader->replaceVariable('NO_SUCH_VARIABLE');
    }
}
