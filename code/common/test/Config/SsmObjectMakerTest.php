<?php


namespace Gustav\Common\Config;

use PHPUnit\Framework\TestCase;

class SsmObjectMakerTest extends TestCase
{
    /**
     * @after
     */
    public function resetInstance(): void
    {
        SsmObjectMaker::resetInstance();
    }

    /**
     * @test
     */
    public function singleton(): void
    {
        $instanceA = SsmObjectMaker::getInstance(DummySsmObject::class, 'dummy');
        $instanceB = SsmObjectMaker::getInstance(DummySsmObject::class, 'dummy');
        SsmObjectMaker::resetInstance();
        $instanceC = SsmObjectMaker::getInstance(DummySsmObject::class, 'dummy');

        $this->assertTrue($instanceA === $instanceB);
        $this->assertTrue($instanceA !== $instanceC);
    }

    /**
     * @test
     */
    public function getObject(): void
    {
        $instance = SsmObjectMaker::getInstance(DummySsmObject::class, 'account', 'region', 'profile');

        $object = $instance->getSsmObject();
        $this->assertInstanceOf(AbstractSsmObject::class, $object);

        $this->assertEquals('account', $object->getAccountFile());
        $this->assertEquals('region', $object->getRegion());
        $this->assertEquals('profile', $object->getProfile());
    }
}
