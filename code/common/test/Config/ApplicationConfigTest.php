<?php


namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ApplicationConfigTest extends TestCase
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
ssm:
  class: Gustav\Common\Config\LocalSsmObject

category:
  key1: value1
  key2: $$MYSQL_USER$$
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
        $loader = new ConfigLoader(self::$tempFilePath);

        $instance = new ApplicationConfig($loader);

        $this->assertEquals('value1', $instance->getValue('category', 'key1'));
        $this->assertEquals('scott', $instance->getValue('category', 'key2'));

        // cached
        $this->assertEquals('scott', $instance->getValue('category', 'key2'));

        $instance2 = new ApplicationConfig($loader);

        // apcu
        $this->assertEquals('scott', $instance2->getValue('category', 'key2'));
    }
}