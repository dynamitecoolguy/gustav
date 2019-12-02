<?php


namespace Gustav\Common\Operation;


use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;
use Redis;

class RankingTest extends TestCase
{
    private static $tempFilePath;
    private static $rankingKey;
    private static $redis = null;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath, 'w');
        fwrite($fd, <<<'__EOF__'
redis:
  host: localhost:16379
__EOF__
        );
        fclose($fd);

        self::$rankingKey = uniqid();
        self::getRedis()->del(self::$rankingKey);
    }

    /**
     * @afterClass
     */
    public static function afterClass(): void
    {
        unlink(self::$tempFilePath);
        self::getRedis()->del(self::$rankingKey);
    }

    /**
     * @return Redis
     * @throws \Exception
     */
    private static function getRedis(): Redis
    {
        if (is_null(self::$redis)) {
            $builder = new BaseContainerBuilder(new ApplicationConfig(new ConfigLoader(self::$tempFilePath)));

            $container = $builder->build();
            $redisI = $container->get(RedisInterface::class);

            self::$redis = $redisI->getRedis();
        }
        return self::$redis;
    }

    /**
     * @before
     * @throws \Exception
     */
    public function reset(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);
        $ranking->reset();
    }

    /**
     * @test
     * @throws \Exception
     */
    public function zero(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $this->assertEquals(0, $ranking->count());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function countMember(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('abc', 0);
        $this->assertEquals(1, $ranking->count());

        $ranking->set('def', 1);
        $this->assertEquals(2, $ranking->count());

        $ranking->incrBy('abc', 5);
        $this->assertEquals(2, $ranking->count());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getMember(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('abc', 8);
        $this->assertEquals(8, $ranking->score('abc'));

        $ranking->set('def', 1234567890);
        $this->assertEquals(1234567890, $ranking->score('def'));

        $ranking->incrBy('abc', 6);
        $this->assertEquals(14, $ranking->score('abc'));

        $ranking->incrBy('abc', -15);
        $this->assertEquals(-1, $ranking->score('abc'));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function ranking1(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('a', 1);
        $ranking->set('b', 2);
        $ranking->set('c', 3);
        $ranking->set('d', 4);
        $ranking->set('e', 5);
        $ranking->set('f', 6);
        $ranking->set('g', 7);
        $ranking->set('h', 8);
        $ranking->set('i', 9);
        $ranking->set('j', 10);

        $this->assertEquals(7, $ranking->rankAsc(7));  // 下から7位
        $this->assertEquals(4, $ranking->rankDesc(7)); // 上から4位

        $this->assertEquals(1, $ranking->rankAsc(0));  // 下から1位
        $this->assertEquals(11, $ranking->rankAsc(11));  // 下から11位

        $this->assertEquals(1, $ranking->rankDesc(11));  // 上から1位
        $this->assertEquals(11, $ranking->rankDesc(0));  // 上から11位
    }

    /**
     * @test
     * @throws \Exception
     */
    public function ranking2(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('a', 1);
        $ranking->set('b', 3);
        $ranking->set('c', 3);
        $ranking->set('d', 3);
        $ranking->set('e', 5);
        $ranking->set('f', 5);
        $ranking->set('g', 7);
        $ranking->set('h', 8);
        $ranking->set('i', 10);
        $ranking->set('j', 10);

        $this->assertEquals(7, $ranking->rankAsc(7));  // 下から7位
        $this->assertEquals(4, $ranking->rankDesc(7)); // 上から4位

        $this->assertEquals(9, $ranking->rankAsc(10));  // 下から9位
        $this->assertEquals(1, $ranking->rankDesc(10)); // 上から1位

        $this->assertEquals(2, $ranking->rankAsc(3));  // 下から2位
        $this->assertEquals(7, $ranking->rankDesc(3)); // 上から7位
    }

    /**
     * @test
     * @throws \Exception
     */
    public function beforeAfter(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('a', 10);
        $ranking->set('b', 30);
        $ranking->set('c', 30);
        $ranking->set('d', 30);
        $ranking->set('e', 50);
        $ranking->set('f', 50);
        $ranking->set('g', 70);
        $ranking->set('h', 80);
        $ranking->set('i', 100);
        $ranking->set('j', 100);

        $this->assertEquals(50, $ranking->greater(40));  // 40の上は50
        $this->assertEquals(30, $ranking->lesser(40)); // 40の下は30

        $this->assertEquals(80, $ranking->greater(70));  // 70の上は80
        $this->assertEquals(50, $ranking->lesser(70)); // 70の下は50

        $this->assertEquals(10, $ranking->greater(0));  // 0の上は80
        $this->assertNull($ranking->lesser(0)); // 0の下はfalse

        $this->assertNull($ranking->greater(100)); // 100の上はfalse
        $this->assertEquals(80, $ranking->lesser(100)); // 100の下は80
    }

    /**
     * @test
     * @throws \Exception
     */
    public function range(): void
    {
        $ranking = new Ranking(self::getRedis(), self::$rankingKey);

        $ranking->set('a', 10);
        $ranking->set('b', 30);
        $ranking->set('c', 30);
        $ranking->set('d', 30);
        $ranking->set('e', 50);
        $ranking->set('f', 50);
        $ranking->set('g', 70);
        $ranking->set('h', 80);
        $ranking->set('i', 100);
        $ranking->set('j', 100);

        $result = $ranking->rangeDesc(1, 10);
        $this->assertEquals([1, 'j', 100], $result[0]);
        $this->assertEquals([1, 'i', 100], $result[1]);
        $this->assertEquals([3, 'h', 80], $result[2]);
        $this->assertEquals([4, 'g', 70], $result[3]);
        $this->assertEquals([5, 'f', 50], $result[4]);
        $this->assertEquals([5, 'e', 50], $result[5]);
        $this->assertEquals([7, 'd', 30], $result[6]);
        $this->assertEquals([7, 'c', 30], $result[7]);
        $this->assertEquals([7, 'b', 30], $result[8]);
        $this->assertEquals([10, 'a', 10], $result[9]);

        $result = $ranking->rangeAsc(1, 10);
        $this->assertEquals([1, 'a', 10], $result[0]);
        $this->assertEquals([2, 'b', 30], $result[1]);
        $this->assertEquals([2, 'c', 30], $result[2]);
        $this->assertEquals([2, 'd', 30], $result[3]);
        $this->assertEquals([5, 'e', 50], $result[4]);
        $this->assertEquals([5, 'f', 50], $result[5]);
        $this->assertEquals([7, 'g', 70], $result[6]);
        $this->assertEquals([8, 'h', 80], $result[7]);
        $this->assertEquals([9, 'i', 100], $result[8]);
        $this->assertEquals([9, 'j', 100], $result[9]);

        $result = $ranking->rangeDesc(10, 15);
        $this->assertEquals([10, 'a', 10], $result[0]);

        $result = $ranking->rangeAsc(2, 3);
        $this->assertEquals([2, 'b', 30], $result[0]);
        $this->assertEquals([2, 'c', 30], $result[1]);
        $this->assertEquals([2, 'd', 30], $result[2]);

        $result = $ranking->rangeAsc(11, 1000);
        $this->assertEmpty($result);
    }

}