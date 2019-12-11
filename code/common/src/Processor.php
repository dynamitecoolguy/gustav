<?php

namespace Gustav\Common;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Operation\BinaryEncryptorInterface;

/**
 * Class Processor
 * @package Gustav\Common
 */
class Processor
{
    /**
     * @param string $input
     * @param Container $container
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception\ModelException
     */
    public static function process(string $input, Container $container): string
    {
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);

        // 復号化
        $decrypted = $encryptor->decrypt($input);

        // デシリアライズ
        $resultList = [];
        $requestObjectList = $serializer->deserialize($decrypted);
        foreach ($requestObjectList as $requestObject) {
            // リクエストオブジェクト毎に処理
            $result = $dispatcher->dispatch($container, $requestObject);
            if (!is_null($result)) {
                $resultList[] = new ModelChunk(
                    $requestObject->getChunkId(),
                    $requestObject->getVersion(),
                    $requestObject->getRequestId(),
                    $result
                );
            }
        }

        // 結果をシリアライズ
        $resultBinary = $serializer->serialize($resultList);

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }
}