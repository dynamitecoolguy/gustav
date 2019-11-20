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
     * @test
     * @throws ConfigException
     */
    public function getValue(): void
    {
        $loader = new ConfigLoader(self::$tempFilePath, DummySsmObject::class);

        $instance = new BaseApplicationConfig($loader);

        $this->assertEquals('value1', $instance->getValue('category', 'key1'));
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance->getValue('category', 'key2'));

        // cached
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance->getValue('category', 'key2'));

        $instance2 = new BaseApplicationConfig($loader);

        // apcu
        $this->assertEquals('HOGEVALUE2_VALUEFUGA', $instance2->getValue('category', 'key2'));
    }
}