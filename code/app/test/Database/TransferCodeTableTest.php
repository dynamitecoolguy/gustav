<?php


namespace Gustav\App\Database;


use Composer\Autoload\ClassLoader;
use Gustav\App\AppContainerBuilder;
use Gustav\App\LocalConfigLoader;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Config\ApplicationConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TransferCodeTableTest extends TestCase
{
    /** @var ContainerInterface */
    private static $container;

    /** @var MySQLAdapter */
    private static $mysql;

    /**
     * @beforeClass
     */
    public static function prepare(): void
    {
        $configLoader =  LocalConfigLoader::createConfigLoader();

        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('Gustav\\App\\', __DIR__ . '/../../src');               // app/src
        $autoloader->addPsr4('Gustav\\Common\\', __DIR__ . '/../../../common/src');  // common/src

        $config = new ApplicationConfig($configLoader);

        $containerBuilder = new AppContainerBuilder($config);
        self::$container = $containerBuilder->build();
        self::$mysql = MySQLAdapter::wrap(self::$container->get(MySQLInterface::class), true);
    }

    /**
     * @afterClass
     */
    public static function clean(): void
    {
        LocalConfigLoader::destroyConfigLoader();
    }

    /**
     * データの整合性がうまく保てなくなるので、テスト実施は保留
     */
    public function insert(): void
    {
        TransferCodeTable::insert(self::$mysql, 3, 'abcdefgh');
    }

    /**
     * @test
     */
    public function dummy(): void
    {
        $this->assertTrue(true);
    }
}