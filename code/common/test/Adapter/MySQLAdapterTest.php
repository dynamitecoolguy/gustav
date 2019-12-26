<?php


namespace Gustav\Common\Adapter;


use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\DatabaseException;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class MySQLAdapterTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function createDummyTable(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $pdo = $adapter->getPDO();

        $statement = $pdo->prepare(<<<'__DUMMY_TABLE__'
create table if not exists __formysqladaptertest(
user_id int unsigned not null,
ival int unsigned null,
sval varchar(8) not null,
bval boolean not null default false,
unique index (user_id)
);
__DUMMY_TABLE__);
        $statement->execute();
    }

    /**
     * @afterClass
     */
    public static function dropDummyTable(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $pdo = $adapter->getPDO();

        $statement = $pdo->prepare('drop table __formysqladaptertest;');
        $statement->execute();
    }

    private static function createConfig(): ApplicationConfigInterface
    {
        return new class implements ApplicationConfigInterface {
            public function getValue(string $category, string $key, ?string $default = null): string
            {
                if ($category == 'mysql') {
                    switch ($key) {
                        case 'host':
                        case 'hostm': return 'localhost:13306';
                        case 'dbname': return 'userdb';
                        case 'user': return 'scott';
                        case 'password': return 'tiger';
                        case 'usecache': return 'false';
                    }
                }
                return $default;
            }
        };
    }

    /**
     * @test
     */
    public function createFromConfig(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $pdo = $adapter->getPDO();
        $statement = $pdo->prepare('select 1 as one');
        $statement->execute();
        $result = $statement->fetch(PDO::FETCH_NUM);

        $this->assertEquals(1, $result[0]);

        $adapter2 = MySQLAdapter::wrap($adapter, false);

        $this->assertEquals($adapter, $adapter2);
    }

    /**
     * @test
     */
    public function wrap(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $pdo = $adapter->getPDO();

        $mysqli = new MySQLAdapterTestDummyInterface($pdo);

        $adapter2 = MySQLAdapter::wrap($mysqli, false);
        $this->assertNotEquals($adapter, $adapter2);
    }

    /**
     * @test
     */
    public function invalidAccess(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $reflect = new ReflectionObject($adapter);
        $property = $reflect->getProperty('user');
        $property->setAccessible(true);
        $property->setValue($adapter, 'dummy');

        $this->expectException(DatabaseException::class);
        $adapter->getPDO();
    }

    /**
     * @test
     */
    public function insertAndFetch(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $adapter->execute('insert __formysqladaptertest(user_id,ival,sval) values(1,2,"aa");');
        $result = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id=1');

        $this->assertEquals([1,2,"aa",0],  $result);
    }

    /**
     * @test
     */
    public function noRecord(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $result = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id=999');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function emptyRecord(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $result = $adapter->fetchAll('select user_id,ival,sval,bval from __formysqladaptertest where user_id=999');

        $this->assertEmpty($result);
    }

    /**
     * @test
     */
    public function insertError(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $adapter->execute('insert __formysqladaptertest(user_id,ival,sval) values(2,2,"aa");');

        $this->expectException(DatabaseException::class);
        $adapter->execute('insert __formysqladaptertest(user_id,ival,sval) values(2,3,"bb");');
    }

    /**
     * @test
     */
    public function prepare(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval,bval) values(:uid,:ival,:sval,:bval);');

        $adapter->execute($statement, ['uid'=>3,'ival'=>10,'sval'=>'hoge','bval'=>true]);
        $adapter->execute($statement, ['uid'=>4,'ival'=>null,'sval'=>'fuga','bval'=>2]);

        $result = $adapter->fetchAll('select user_id,ival,sval,bval from __formysqladaptertest where user_id in (3,4)');

        if ($result[0][0] == 3) {
            $user3 = $result[0];
            $user4 = $result[1];
        } else {
            $user3 = $result[1];
            $user4 = $result[0];
        }

        $this->assertEquals([3,10,'hoge',1], $user3);
        $this->assertEquals([4,null,'fuga',2], $user4);
    }

    /**
     * @test
     */
    public function prepareFailed(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $this->expectException(DatabaseException::class);
        $adapter->prepare('hoge?');
    }

    /**
     * @test
     */
    public function prepareFailed2(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $this->expectException(DatabaseException::class);
        $adapter->prepare(1);
    }

    /**
     * @test
     */
    public function bindFailed(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval,bval) values(:uid,:ival,:sval,:bval);');

        $this->expectException(DatabaseException::class);
        $adapter->execute($statement, ['uid'=>5]);
    }

    /**
     * @test
     */
    public function bindFailed2(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval,bval) values(:uid,:ival,:sval,:bval);');

        $this->expectException(DatabaseException::class);
        $adapter->execute($statement, ['nosuch'=>5]);
    }

    /**
     * @test
     */
    public function executeFailed(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $this->expectException(DatabaseException::class);
        $adapter->execute('hoge?');
    }

    /**
     * @test
     */
    public function executeFailed2(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $this->expectException(DatabaseException::class);
        $adapter->execute(1);
    }

    /**
     * @test
     */
    public function transactionForSlave(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $this->expectException(DatabaseException::class);
        $adapter->beginTransaction();
    }

    /**
     * @test
     */
    public function commitForSlave(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $this->expectException(DatabaseException::class);
        $adapter->commit();
    }

    /**
     * @test
     */
    public function rollBackForSlave(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());
        $this->expectException(DatabaseException::class);
        $adapter->rollBack();
    }

    /**
     * @test
     */
    public function transactionNested(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);
        $adapter->beginTransaction();
        $this->expectException(DatabaseException::class);
        $adapter->beginTransaction();
    }

    /**
     * @test
     */
    public function transactionSuccess(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $this->assertFalse($adapter->isInTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>6,'ival'=>6,'sval'=>'6']);
        $result1 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 6');
        $adapter->commit();
        $this->assertFalse($adapter->isInTransaction());
        $result2 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 6');

        $this->assertEquals([6,6,'6',0], $result1);
        $this->assertEquals([6,6,'6',0], $result2);
    }

    /**
     * @test
     */
    public function transactionCancelled(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $this->assertFalse($adapter->isInTransaction());
        $adapter->beginTransaction();
        $this->assertTrue($adapter->isInTransaction());
        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>7,'ival'=>7,'sval'=>'7']);
        $result1 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 7');
        $adapter->rollBack();
        $this->assertFalse($adapter->isInTransaction());
        $result2 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 7');

        $this->assertEquals([7,7,'7',0], $result1);
        $this->assertNull($result2);
    }

    private $executeWithTransactionFlag1 = false;
    private $executeWithTransactionFlag2 = false;

    /**
     * @test
     */
    public function executeWithTransaction(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $this->executeWithTransactionFlag1 = false;
        $this->executeWithTransactionFlag2 = false;

        $result = $adapter->executeWithTransaction(
            [$this, 'session1'],
            $this,
            [$this, 'succeeded'],
            [$this, 'failed']
        );

        $this->assertEquals('OK', $result);

        $result = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 8');
        $this->assertEquals([8,8,'8',0], $result);

        $this->assertTrue($this->executeWithTransactionFlag1);
        $this->assertFalse($this->executeWithTransactionFlag2);
    }

    /**
     * @test
     */
    public function executeWithTransactionFailed(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $this->executeWithTransactionFlag1 = false;
        $this->executeWithTransactionFlag2 = false;

        try {
            $result = $adapter->executeWithTransaction(
                [$this, 'session2'],
                $this,
                [$this, 'succeeded'],
                [$this, 'failed']
            );
        } catch (DatabaseException $e) {
            $this->assertEquals(DatabaseException::TRANSACTION_FAILED, $e->getCode());
            $result = 'FAILED';
        }

        $this->assertEquals('FAILED', $result);

        $result = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 9');
        $this->assertNull($result);

        $this->assertFalse($this->executeWithTransactionFlag1);
        $this->assertTrue($this->executeWithTransactionFlag2);
    }

    /**
     * @test
     */
    public function executeWithTransactionCancel(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $this->executeWithTransactionFlag1 = false;
        $this->executeWithTransactionFlag2 = false;

        $result = $adapter->executeWithTransaction(
            [$this, 'session3'],
            $this,
            [$this, 'succeeded'],
            [$this, 'failed']
        );

        $this->assertEquals('CANCELLED', $result);

        $result = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 10');
        $this->assertNull($result);

        $this->assertFalse($this->executeWithTransactionFlag1);
        $this->assertTrue($this->executeWithTransactionFlag2);
    }

    /**
     * @test
     */
    public function executeWithTransactionSlave(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $this->expectException(DatabaseException::class);
        $adapter->executeWithTransaction(
            [$this, 'session1'],
            $this,
            [$this, 'succeeded'],
            [$this, 'failed']
        );
    }

    public function session1($adapter, $self): string
    {
        /** @var MySQLAdapter $adapter */
        $this->assertEquals($self, $this);
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>8,'ival'=>8,'sval'=>'8']);

        return 'OK';
    }

    public function session2($adapter, $self): string
    {
        /** @var MySQLAdapter $adapter */
        $this->assertEquals($self, $this);
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>9,'ival'=>9,'sval'=>'9']);

        throw new \Exception();
    }

    public function session3($adapter, $self): string
    {
        /** @var MySQLAdapter $adapter */
        $this->assertEquals($self, $this);
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>10,'ival'=>10,'sval'=>'10']);

        $adapter->cancelTransaction();

        return 'CANCELLED';
    }

    public function succeeded($adapter, $result, $self): void
    {
        $this->assertEquals($self, $this);
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $this->executeWithTransactionFlag1 = true;
    }

    public function failed($adapter, $self): void
    {
        $this->assertEquals($self, $this);
        $this->assertInstanceOf(MySQLAdapter::class, $adapter);

        $this->executeWithTransactionFlag2 = true;
    }

    /**
     * @test
     */
    public function timestamp(): void
    {
        $adapter = MySQLAdapter::create(static::createConfig());

        $data1 = [1, 'aa', '2019-12-24 00:00:00'];
        $converted1 = $adapter->parseTimestamp($data1, 2);
        $this->assertEquals([1, 'aa', 1577145600], $converted1);
        $converted2 = $adapter->parseTimestamp($data1, [2]);
        $this->assertEquals([1, 'aa', 1577145600], $converted2);

        $data2 = [1, 'aa', '1999-12-24 00:00:00', '2019-12-24 00:00:00'];
        $converted3 = $adapter->parseTimestamp($data2, 3);
        $this->assertEquals([1, 'aa', '1999-12-24 00:00:00', 1577145600], $converted3);
        $converted4 = $adapter->parseTimestamp($data2, [2, 3]);
        $this->assertEquals([1, 'aa', 945993600, 1577145600], $converted4);

        $data3 = [
            [1, 'aa', '1999-12-24 00:00:00', '2019-12-24 00:00:00'],
            [2, 'bb', '1999-12-24 00:01:00', '2019-12-24 00:02:00']
        ];
        $converted5 = $adapter->parseTimestamp($data3, [2, 3]);
        $this->assertEquals([
            [1, 'aa', 945993600, 1577145600],
            [2, 'bb', 945993660, 1577145720]
        ], $converted5);
    }
}

class MySQLAdapterTestDummyInterface implements MySQLInterface
{
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function getPDO(): PDO { return $this->pdo; }
}