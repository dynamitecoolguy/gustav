<?php


namespace Gustav\Common\Network;


use PHPUnit\Framework\TestCase;

class NameResolverTest extends TestCase
{
    /**
     * @test
     */
    public function getLocalhost(): void
    {
        NameResolver::flushCache('localhost');
        $this->assertEquals(NameResolver::getIp('localhost'), '127.0.0.1');
        $this->assertEquals(NameResolver::getIp('localhost'), '127.0.0.1'); // cache used
    }
}