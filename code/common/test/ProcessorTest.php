<?php


namespace Gustav\Common;


use Composer\Autoload\ClassLoader;
use DI\Container;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelClassMap;
use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\MonsterModel;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use PHPUnit\Framework\TestCase;


class ProcessorTest extends TestCase
{
    /**
     * @beforeClass
     * @throws Exception\ModelException
     */
    public static function setAutoLoader()
    {
        /** @var ClassLoader $autoloader */
        $autoloader = require __DIR__ . '/../../vendor/autoload.php';
        $autoloader->addPsr4('', __DIR__ . '/../../flatbuffers/example/php');              // flatbuffers/php

        ModelClassMap::resetMap();
        ModelClassMap::registerModel('MON', MonsterModel::class);
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
        $outputData = Processor::process($inputData, $container);

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);
        $outputArray = $serializer->deserialize($encryptor->decrypt($outputData));

        // 確認
        $this->assertEquals(1, $outputArray[0]->getVersion());
        $this->assertEquals('req1', $outputArray[0]->getRequestId());
        $this->assertEquals('gaia', $outputArray[0]->getModel()->name);
        $this->assertEquals(11, $outputArray[0]->getModel()->hp);
    }

    private function getInputData(Container $container)
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
        $stream = $serializer->serialize([
            new ModelChunk('MON', 1, 'req1', $monster1),
            new ModelChunk('MON', 1, 'req2', $monster2),
            new ModelChunk('MON', 1, 'req3', $monster3)
        ]);

        $encryptor = $container->get(BinaryEncryptorInterface::class);
        return $encryptor->encrypt($stream);
    }
}

class DummyDispatcher implements DispatcherInterface
{
    public function dispatch(Container $container, ModelChunk $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();
        if ($request instanceof MonsterModel) {
            $request->hp -= 100;
        }
        return $request;
    }
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
