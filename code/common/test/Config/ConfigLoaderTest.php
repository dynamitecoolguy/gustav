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
     * @after
     */
    public function resetInstance(): void
    {
        ConfigLoader::resetInstance();
        SsmObjectMaker::resetInstance();
    }

    /**
     * @test
     */
    public function singleton(): void
    {
        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');

        $instanceA = ConfigLoader::getInstance('dummy', $ssmObjectMaker);
        $instanceB = ConfigLoader::getInstance('dummy', $ssmObjectMaker);
        ConfigLoader::resetInstance();
        $instanceC = ConfigLoader::getInstance('dummy', $ssmObjectMaker);

        $this->assertTrue($instanceA === $instanceB);
        $this->assertTrue($instanceA !== $instanceC);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function getValidValue(): void
    {
        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);
        $this->assertEquals($loader->getConfig('dynamodb', 'table'), 'hogehoge');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidCategory(): void
    {
        $this->expectException(ConfigException::class);

        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);
        $loader->getConfig('no_such_category', 'table');
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function invalidKey(): void
    {
        $this->expectException(ConfigException::class);

        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);
        $loader->getConfig('dynamodb', 'no_such_key');
    }

    /**
     * @test
     */
    public function allVariables(): void
    {
        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);
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
        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);

        $this->assertEquals('LOG_DB_USER_VALUE', $loader->replaceVariable('LOG_DB_USER'));
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $loader->replaceVariable('LOG_DB_PASSWORD'));

        // cache used
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $loader->replaceVariable('LOG_DB_PASSWORD'));

        // apc used
        ConfigLoader::resetInstance();
        $anotherLoader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);
        $this->assertEquals('LOG_DB_PASSWORD_VALUE', $anotherLoader->replaceVariable('LOG_DB_PASSWORD'));
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function replaceValueFailure(): void
    {
        $this->expectException(ConfigException::class);

        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'ssm');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);

        $loader->replaceVariable('NO_SUCH_VARIABLE');
    }
}
