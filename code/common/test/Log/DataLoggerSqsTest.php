<?php


namespace Gustav\Common\Log;

use Aws\Sdk;
use PHPUnit\Framework\TestCase;

class DataLoggerSqsTest extends TestCase
{
    private static $client;

    private static $queueUrl = 'http://localhost:19324/queue/hoge_queue';

    /**
     * @beforeClass
     */
    public static function setUpSqsClient()
    {
        $sdk = new Sdk([
            'endpoint' => 'http://localhost:19324',
            'region' => 'ap-northeast-1',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => 'x',
                'secret' => 'x'
            ]
        ]);
        self::$client = $sdk->createSqs();
    }

    /**
     * @test
     */
    public function createLogger()
    {
        $logger = DataLoggerSqs::getInstance(self::$client, self::$queueUrl);
        $this->assertInstanceOf(DataLoggerSqs::class, $logger);
    }

    /**
     * @test
     */
    public function singleLog()
    {
        $logger = DataLoggerSqs::getInstance(self::$client, self::$queueUrl);

        $logger->add('test.tag', microtime(true), ['body' => 'testData']);
        $logger->flush();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function multiLog()
    {
        $logger = DataLoggerSqs::getInstance(self::$client, self::$queueUrl);

        $logger->add('test.tag', microtime(true), ['body' => 'testData1']);
        $logger->add('test.tag', microtime(true), ['body' => 'testData2']);
        $logger->add('test.tag', microtime(true), ['body' => 'testData3']);
        $logger->flush();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function clear()
    {
        $logger = DataLoggerSqs::getInstance(self::$client, self::$queueUrl);

        $logger->add('test.cleared', microtime(true), ['body' => 'testData']);
        $logger->add('test.cleared', microtime(true), ['body' => 'testData']);
        $logger->clear();
        $logger->flush();

        $this->assertTrue(true);
    }
}
