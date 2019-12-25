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
    public function transactionSuccess(): void
    {
        $adapter = MySQLAdapter::wrap(MySQLAdapter::create(static::createConfig()), true);

        $adapter->beginTransaction();
        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>6,'ival'=>6,'sval'=>'6']);
        $result1 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 6');
        $adapter->commit();
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

        $adapter->beginTransaction();
        $statement = $adapter->prepare('insert __formysqladaptertest(user_id,ival,sval) values(:uid,:ival,:sval);');
        $adapter->execute($statement, ['uid'=>7,'ival'=>7,'sval'=>'7']);
        $result1 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 7');
        $adapter->rollBack();
        $result2 = $adapter->fetch('select user_id,ival,sval,bval from __formysqladaptertest where user_id = 7');

        $this->assertEquals([7,7,'7',0], $result1);
        $this->assertNull($result2);
    }
}

class MySQLAdapterTestDummyInterface implements MySQLInterface
{
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function getPDO(): PDO { return $this->pdo; }
}