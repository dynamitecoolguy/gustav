<?php


namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;
use PHPUnit\Framework\TestCase;

class ApplicationConfigTest extends TestCase
{
    private static $tempFilePath1;
    private static $tempFilePath2;

    /**
     * @beforeClass
     */
    public static function beforeClass(): void
    {
        self::$tempFilePath1 = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath1, 'w');
        fwrite($fd, <<<'__EOF__'
category1:
  key1: value1
  key2: $$MYSQL_USER$$

category2:
  key1: value2
__EOF__
        );
        fclose($fd);

        self::$tempFilePath2 = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath2, 'w');
        fwrite($fd, <<<'__EOF__'
category1:
  key1: value1
  key2: scott

category2:
  key2: tiger
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
        $loader = new ConfigLoader(self::$tempFilePath1, self::$tempFilePath2);

        $instance = new ApplicationConfig($loader);

        $this->assertEquals('value1', $instance->getValue('category1', 'key1'));
        $this->assertEquals('scott', $instance->getValue('category1', 'key2'));
        $this->assertEquals('value2', $instance->getValue('category2', 'key1'));
        $this->assertEquals('tiger', $instance->getValue('category2', 'key2'));

        // cached
        $this->assertEquals('scott', $instance->getValue('category1', 'key2'));

        $instance2 = new ApplicationConfig($loader);

        // apcu
        $this->assertEquals('scott', $instance2->getValue('category1', 'key2'));
    }
}