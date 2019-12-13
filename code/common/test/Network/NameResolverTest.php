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
        $this->assertEquals('127.0.0.1', NameResolver::getIp('localhost'));
        $this->assertEquals('127.0.0.1', NameResolver::getIp('localhost'));
    }

    /**
     * @test
     */
    public function resolve(): void
    {
        NameResolver::flushCache('localhost');
        $this->assertEquals(['127.0.0.1', 0], NameResolver::resolveHostAndPort('localhost'));
        $this->assertEquals(['127.0.0.1', 8080], NameResolver::resolveHostAndPort('localhost:8080'));
    }
}