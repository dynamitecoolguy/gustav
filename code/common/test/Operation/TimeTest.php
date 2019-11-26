<?php


namespace Gustav\Common\Operation;

use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
{
    /**
     * @test
     */
    public function now()
    {
        $now1 = Time::now();
        usleep(100000);
        $now2 = Time::now();

        $this->assertEquals($now1, $now2);
    }
}
