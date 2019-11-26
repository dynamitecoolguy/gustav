<?php


namespace Gustav\Common\Log;

use PHPUnit\Framework\TestCase;

class DataLoggerFluentTest extends TestCase
{
    /**
     * @test
     */
    public function createLogger()
    {
        $logger = DataLoggerFluent::getInstance('localhost', 24224);
        $this->assertInstanceOf(DataLoggerFluent::class, $logger);
    }

    /**
     * @test
     */
    public function singleLog()
    {
        $logger = DataLoggerFluent::getInstance('localhost', 24224);

        $logger->add('test.tag', microtime(true), ['body' => 'testData']);
        $logger->flush();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function clear()
    {
        $logger = DataLoggerFluent::getInstance('localhost', 24224);

        $logger->add('test.cleared', microtime(true), ['body' => 'testData']);
        $logger->add('test.cleared', microtime(true), ['body' => 'testData']);
        $logger->clear();
        $logger->flush();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function entity()
    {
        $now = microtime(true);
        $data = ['hoge' => 'fuga'];
        $entity = new EntityUs('tag', $data, $now);

        $this->assertEquals('tag', $entity->getTag());
        $this->assertEquals(intval($now), $entity->getTime());
        $this->assertEquals(['now' => $now, 'hoge' => 'fuga'], $entity->getData());
        $this->assertEquals($now, $entity->getMicroTime());
    }
}