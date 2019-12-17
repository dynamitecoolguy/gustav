<?php


namespace Gustav\App\Operation;

use Composer\Autoload\ClassLoader;
use DI\Container;
use Gustav\App\AppContainerBuilder;
use Gustav\App\LocalConfigLoader;
use Gustav\App\RedisKeys;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Config\ApplicationConfig;
use PHPUnit\Framework\TestCase;

class OpenIdConverterTest extends TestCase
{
    /** @var Container */
    private static $container;

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
    }

    /**
     * @afterClass
     */
    public static function clean(): void
    {
        LocalConfigLoader::destroyConfigLoader();
    }

    /**
     * @test
     */
    public function mseq()
    {
        $converter = new OpenIdConverter();
        $redis = self::$container->get(RedisInterface::class);

        $initialValue = substr('000000000' . strval(OpenIdConverter::INIT_VALUE), -10, 10);

        $this->assertEquals($initialValue, $converter->userIdToOpenId($redis, 0));

        $redisManager = self::$container->get(RedisInterface::class);

        // キャッシュ無しからの計算
        $redisManager->del(RedisKeys::KEY_OPEN_ID);
        $openId = $converter->userIdToOpenId($redis, 10);

        // キャッシュからの計算
        $this->assertEquals($openId, $converter->userIdToOpenId($redis, 10));
    }
}