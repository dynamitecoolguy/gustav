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
ssm:
  class: Gustav\Common\Config\LocalSsmObject

mysql:
  host: mysql
  dbname: userdb
  user: $$MYSQL_USER$$
  password: $$MYSQL_PASSWORD$$

pgsql:
  host: pgsql
  dbname: logdb
  user: $$PGSQL_USER$$
  password: $$PGSQL_PASSWORD$$

redis:
  host: redis

dynamodb:
  endpoint: http://dynamodb:8000
  region: ap-northeast-1
  key: $$DYNAMODB_ACCESSKEY$$
  secret: $$DYNAMODB_SECRET$$
  table: hogehoge

storage:
  endpoint: http://storage:9000
  region: ap-northeast-1
  key: $$STORAGE_ACCESSKEY$$
  secret: $$STORAGE_SECRET$$
  bucket: $$STORAGE_BUCKET$$
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
        $loader = new ConfigLoader(self::$tempFilePath);
        $this->assertEquals($loader->getConfig('dynamodb', 'table', null), 'hogehoge');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidCategory(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath);
        $loader->getConfig('no_such_category', 'table', null);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidKey(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath);
        $loader->getConfig('dynamodb', 'no_such_key', null);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function defaultValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath);
        $this->assertEquals('default', $loader->getConfig('dynamodb', 'no_such_key_too', 'default'));
    }

    /**
     * @test
     */
    public function allVariables(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath);
        $values = $loader->getAllVariables();
        sort($values);
        $this->assertSame($values, [
            'DYNAMODB_ACCESSKEY', 'DYNAMODB_SECRET',
            'MYSQL_PASSWORD', 'MYSQL_USER',
            'PGSQL_PASSWORD', 'PGSQL_USER',
            'STORAGE_ACCESSKEY', 'STORAGE_BUCKET', 'STORAGE_SECRET'
        ]);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function replaceValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath);

        $this->assertEquals('scott', $loader->replaceVariable('PGSQL_USER'));
        $this->assertEquals('tiger', $loader->replaceVariable('PGSQL_PASSWORD'));

        // cache used
        $this->assertEquals('tiger', $loader->replaceVariable('PGSQL_PASSWORD'));

        // apc used
        $anotherLoader = new ConfigLoader(self::$tempFilePath);
        $this->assertEquals('tiger', $anotherLoader->replaceVariable('PGSQL_PASSWORD'));
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function replaceValueFailure(): void
    {
        $this->expectException(ConfigException::class);

        $loader = new ConfigLoader(self::$tempFilePath);

        $loader->replaceVariable('NO_SUCH_VARIABLE');
    }
}
