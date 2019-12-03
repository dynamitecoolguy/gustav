<?php


namespace Gustav\App\Controller;

use Composer\Autoload\ClassLoader;
use DI\Container;
use Fig\Http\Message\StatusCodeInterface;
use Gustav\App\AppContainerBuilder;
use Gustav\App\DispatcherInterface;
use Gustav\Common\Config\ApplicationConfig;
use Gustav\Common\Config\ConfigLoader;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Model\ModelSerializer;
use Gustav\Common\Model\Monster;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use GuzzleHttp\Psr7\BufferStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Psr7\Uri;

class MainControllerTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../../flatbuffers/example/php');              // flatbuffers/php

        ModelClassMap::registerModel(Monster::class);
    }

    private static $tempFilePath;

    /**
     * @beforeClass
     */
    public static function createConfig(): void
    {
        self::$tempFilePath = tempnam('/tmp', 'configloadertest');

        $fd = fopen(self::$tempFilePath, 'w');
        fwrite($fd, <<<'__EOF__'
mysql:
  host: localhost:13306
  dbname: userdb
  user: scott
  password: tiger
__EOF__
        );
        fclose($fd);
    }

    /**
     * @afterClass
     */
    public static function destroyConfig(): void
    {
        unlink(self::$tempFilePath);
    }

    /**
     * @test
     */
    public function main()
    {
        // getContainer
        $config = new ApplicationConfig(new ConfigLoader(self::$tempFilePath));
        $builder = new DummyContainerBuilder($config);
        $container = $builder->build();

        // input data
        $inputData = $this->getInputData($container);

        // 準備
        $stream = new BufferStream();
        $stream->write($inputData);
        $request = new DummyServerRequestInterface($stream);

        // POST
        $controller = new MainController();
        $response = new Response(StatusCodeInterface::STATUS_OK, null, new BufferStream());
        $response = $controller->post($request, $container, $response);

        $outputData = $response->getBody()->getContents();

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $outputArray = ModelSerializer::deserialize($encryptor->decrypt($outputData));

        // 確認
        $this->assertEquals('req1', $outputArray[0][0]);
        $this->assertEquals('gaia', $outputArray[0][1]->name);
        $this->assertEquals(11, $outputArray[0][1]->hp);
    }

    private function getInputData(Container $container)
    {
        $monster1 = new Monster();
        $monster1->name = 'gaia';
        $monster1->hp = 111;

        $monster2 = new Monster();
        $monster2->name = 'ortega';
        $monster2->hp = 222;

        $monster3 = new Monster();
        $monster3->name = 'mash';
        $monster3->hp = 333;

        $stream = ModelSerializer::serialize([['req1', $monster1], ['req2', $monster2], ['req3', $monster3]]);

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        return $encryptor->encrypt($stream);
    }
}

class DummyServerRequestInterface extends Request
{
    public function __construct(StreamInterface $body)
    {
        parent::__construct('get', new Uri('http', 'localhost'), new Headers(), [], [], $body, []);
    }
}

class DummyDispatcher implements DispatcherInterface
{
    public function dispatch(Container $container, ModelInterface $request): ?ModelInterface
    {
        if ($request instanceof Monster) {
            $request->hp -= 100;
        }
        return $request;
    }
}

class DummyContainerBuilder extends AppContainerBuilder
{
    protected function getDefinitions(ApplicationConfig $config): array
    {
        $definitions = parent::getDefinitions($config);
        $definitions[DispatcherInterface::class] = new DummyDispatcher();
        return $definitions;
    }
}
