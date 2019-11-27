<?php


namespace Gustav\Common;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Adapter\DynamoDbInterface;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\PgSQLInterface;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Adapter\S3Interface;
use Gustav\Common\Adapter\SqsInterface;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Config\ConfigLoader;
use Gustav\Common\Log\DataLoggerInterface;
use Gustav\Common\Operation\BinaryEncryptor;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use PHPUnit\Framework\TestCase;

class BaseContainerBuilderTest extends TestCase
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
  host: localhost:13306
  dbname: userdb
  user: $$MYSQL_USER$$
  password: $$MYSQL_PASSWORD$$

pgsql:
  host: localhost:15432
  dbname: logdb
  user: $$PGSQL_USER$$
  password: $$PGSQL_PASSWORD$$

redis:
  host: localhost:16379

dynamodb:
  endpoint: http://localhost:18000
  region: ap-northeast-1
  key: $$DYNAMODB_ACCESS_KEY$$
  secret: $$DYNAMODB_SECRET$$
  table: hogehoge

storage:
  endpoint: http://localhost:19000
  region: ap-northeast-1
  key: $$STORAGE_ACCESS_KEY$$
  secret: $$STORAGE_SECRET$$
  bucket: $$STORAGE_BUCKET$$

sqs:
  endpoint: http://localhost:19000
  key: hoge
  secret: fuga

logger:
  type: fluent
  host: localhost:24224
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
     * @throws \Exception
     */
    public function getConfig(): void
    {
        $config = new ApplicationConfig(new ConfigLoader(self::$tempFilePath));
        $builder = new BaseContainerBuilder($config);
        $container = $builder->build();

        $this->assertSame($config, $container->get(ApplicationConfigInterface::class));
    }

    /**
     * @return Container
     * @throws \Exception
     */
    private function getContainer(): Container
    {
        $builder = new BaseContainerBuilder(new ApplicationConfig(new ConfigLoader(self::$tempFilePath)));
        return $builder->build();
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getMySQL(): void
    {
        $container = $this->getContainer();
        $mysqli = $container->get(MySQLInterface::class);
        $pdo = $mysqli->getPDO();

        $statement = $pdo->query('SELECT 1');
        $row = $statement->fetch();

        $this->assertEquals(1, $row[1]);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getPgSQL(): void
    {
        $container = $this->getContainer();
        $pgsqli = $container->get(PgSQLInterface::class);
        $pdo = $pgsqli->getPDO();

        $statement = $pdo->query('SELECT 1 one');
        $row = $statement->fetch();

        $this->assertEquals(1, $row['one']);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRedis(): void
    {
        $container = $this->getContainer();
        $redisi = $container->get(RedisInterface::class);
        $redis = $redisi->getRedis();

        $key = '___DUMMY___';
        $redis->setex($key, 1, 'valuevalue');
        $this->assertEquals('valuevalue', $redis->get('___DUMMY___'));
        $redis->del($key);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDynamo(): void
    {
        $container = $this->getContainer();
        $dynamoi = $container->get(DynamoDbInterface::class);
        $dynamo = $dynamoi->getClient();

        $config = $dynamo->getConfig();

        $this->assertEquals('dynamodb', $config['signing_name']);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getS3(): void
    {
        $container = $this->getContainer();
        $s3i = $container->get(S3Interface::class);
        $s3 = $s3i->getClient();

        $config = $s3->getConfig();

        $this->assertEquals('s3', $config['signing_name']);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSqs(): void
    {
        $container = $this->getContainer();
        $sqsi = $container->get(SqsInterface::class);
        $sqs = $sqsi->getClient();

        $config = $sqs->getConfig();

        $this->assertEquals('sqs', $config['signing_name']);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function binaryEncryptor(): void
    {
        $container = $this->getContainer();
        $encryptor = $container->get(BinaryEncryptorInterface::class);

        $this->assertInstanceOf(BinaryEncryptor::class, $encryptor);
    }

    /**
     * @test
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getLogger(): void
    {
        $container = $this->getContainer();
        $logger = $container->get(DataLoggerInterface::class);

        $this->assertInstanceOf(DataLoggerInterface::class, $logger);
    }
}
