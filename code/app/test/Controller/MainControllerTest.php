<?php


namespace Gustav\App\Controller;
use Composer\Autoload\ClassLoader;
use Gustav\App\AppContainerBuilder;
use Gustav\App\LocalConfigLoader;
use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Config\ApplicationConfig;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class MainControllerTest extends TestCase
{
    /** @var ContainerInterface */
    public static $container;

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
        $autoloader->addPsr4('Gustav\\Dx\\', __DIR__ . '/../../../flatbuffers/php');             // flatbuffers/php

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
    public function requestUnsealed(): void
    {
        $identification = new IdentificationModel();
        $identification->setNote('hogehoge');

        $content = json_encode([
            ['REG', 1, 'req', $identification->serializePrimitive()],
            '1234'
        ]);

        $request = $this->createRequest($content);
        $response = new Response();

        $controller = new MainController();

        $result = $controller->unsealed($request, self::$container, $response);

        $decoded = json_decode($result->getBody());

        $this->assertEquals('1234', $decoded[1]);
        $this->assertEquals('REG', $decoded[0][0]);
        $this->assertEquals(1, $decoded[0][1]);
        $this->assertEquals('req', $decoded[0][2]);

        $resultModel = IdentificationModel::deserializePrimitive(1, $decoded[0][3]);

        $this->assertGreaterThan(0, $resultModel->getUserId());
        $this->assertNotEmpty($resultModel->getOpenId());
        $this->assertEquals('hogehoge', $resultModel->getNote());
        $this->assertStringStartsWith('-----BEGIN PRIVATE', $resultModel->getPrivateKey());
        $this->assertStringStartsWith('-----BEGIN PUBLIC', $resultModel->getPublicKey());
    }

    private function createRequest(string $content): Request
    {
        $method = 'get';
        $uri = (new UriFactory())->createUri('http://app.localhost/unsealed');

        $headers = new Headers();
        $body = (new StreamFactory())->createStream($content);

        return new Request($method, $uri, $headers, [], [], $body);
    }
}