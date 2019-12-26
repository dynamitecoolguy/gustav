<?php


namespace Gustav\Common\Network;


use Composer\Autoload\ClassLoader;
use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelMapper;
use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\MonsterModel;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;


class ProcessorTest extends TestCase
{
    /**
     * @beforeClass
     * @throws Exception\ModelException
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../../flatbuffers/example/php');              // flatbuffers/php

        ModelMapper::resetMap();
        ModelMapper::registerModel('MON', MonsterModel::class);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function main()
    {
        // getContainer
        $builder = new DummyContainerBuilder(
            new class implements ApplicationConfigInterface {
                public function getValue(string $category, string $key, ?string $default = null): string
                {
                    if ($category == 'serializer' && $key == 'type') {
                        return 'flatbuffers';
                    }
                    return 'dummy';
                }
            }
        );
        $container = $builder->build();

        // input data
        $inputData = $this->getInputData($container);

        // Processing
        $invoker = new Invoker(
            new ResolverChain([
                new AssociativeArrayResolver(),
                new TypeHintContainerResolver($container)
            ]),
            $container);
        $outputData = $invoker->call(
            [Processor::class, 'process'],
            ['input' => $inputData]
        );

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);
        $outputParcel = $serializer->deserialize($encryptor->decrypt($outputData));
        $outputArray = $outputParcel->getPackList();

        // 確認
        $this->assertEquals('', $outputParcel->getToken());
        $this->assertEquals(1, $outputArray[0]->getVersion());
        $this->assertEquals('req1', $outputArray[0]->getRequestId());
        $this->assertEquals('gaia', $outputArray[0]->getModel()->name);
        $this->assertEquals(11, $outputArray[0]->getModel()->hp);
    }

    private function getInputData(ContainerInterface $container)
    {
        $monster1 = new MonsterModel();
        $monster1->name = 'gaia';
        $monster1->hp = 111;

        $monster2 = new MonsterModel();
        $monster2->name = 'ortega';
        $monster2->hp = 222;

        $monster3 = new MonsterModel();
        $monster3->name = 'mash';
        $monster3->hp = 333;

        $serializer = $container->get(ModelSerializerInterface::class);
        $stream = $serializer->serialize(new Parcel(
            'mytoken',
            [
                new Pack('MON', 1, 'req1', $monster1),
                new Pack('MON', 1, 'req2', $monster2),
                new Pack('MON', 1, 'req3', $monster3)
            ]
        ));

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        return $encryptor->encrypt($stream);
    }
}

class DummyDispatcher implements DispatcherInterface
{
    public function dispatch(?int $userId, ContainerInterface $container, Pack $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();
        if ($request instanceof MonsterModel) {
            $request->hp -= 100;
        }
        return $request;
    }

    public function isTokenRequired(Pack $requestToken): bool { return false; }
}

class DummyContainerBuilder extends BaseContainerBuilder
{
    protected function getDefinitions(ApplicationConfigInterface $config): array
    {
        $definitions = parent::getDefinitions($config);
        $definitions[DispatcherInterface::class] = new DummyDispatcher();
        return $definitions;
    }
}
