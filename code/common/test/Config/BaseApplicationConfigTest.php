<?php


namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class BaseApplicationConfigTest extends TestCase
{
    private static $tempFilePath;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath, 'w');
        fwrite($fd, <<<'__EOF__'
category:
  key1: value1
  key2: HOGE$$VALUE2$$FUGA
__EOF__
        );
        fclose($fd);
    }

    /**
     * @after
     */
    public function resetInstance(): void
    {
        BaseApplicationConfig::resetInstance();
        SsmObjectMaker::resetInstance();
        ConfigLoader::resetInstance();
    }

    /**
     * @test
     */
    public function singleton(): void
    {
        $ssmObjectMaker = SsmObjectMaker::getInstance(SsmObject::class, 'dummy');
        $configLoader = ConfigLoader::getInstance('dummy', $ssmObjectMaker);

        $instanceA = BaseApplicationConfig::getInstance($configLoader);
        $instanceB = BaseApplicationConfig::getInstance($configLoader);
        BaseApplicationConfig::resetInstance();
        $instanceC = BaseApplicationConfig::getInstance($configLoader);

        $this->assertTrue($instanceA === $instanceB);
        $this->assertTrue($instanceA !== $instanceC);
    }

    /**
     * @test
     * @throws ConfigException
     */
    public function getValue(): void
    {
        $ssmObjectMaker= SsmObjectMaker::getInstance(DummySsmObject::class, 'dummy');
        $loader = ConfigLoader::getInstance(self::$tempFilePath, $ssmObjectMaker);

        $instance = BaseApplicationConfig::getInstance($loader);

        $this->assertEquals('value1', $instance->getValue('category', 'key1'));
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance->getValue('category', 'key2'));

        // cached
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance->getValue('category', 'key2'));

        BaseApplicationConfig::resetInstance();
        $instance2 = BaseApplicationConfig::getInstance($loader);

        // apcu
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance2->getValue('category', 'key2'));
    }
}