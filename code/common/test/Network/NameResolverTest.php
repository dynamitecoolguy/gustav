<?php


namespace Gustav\Common\Network;


use PHPUnit\Framework\TestCase;

class NameResolverTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public static function setUpBeforeClass(): void
    {
        $reflection = new \ReflectionClass(NameResolver::class);
        $method = $reflection->getMethod('makeKey');
        $method->setAccessible(true);

        apcu_delete($method->invoke(null, 'localhost'));
    }

    public function testGetLocalhost(): void
    {
        $this->assertEquals(NameResolver::getIp('localhost'), '127.0.0.1');
        $this->assertEquals(NameResolver::getIp('localhost'), '127.0.0.1'); // cache used
    }
}