<?php

namespace Gustav\Common;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelSerializer;
use Gustav\Common\Operation\BinaryEncryptorInterface;

class Processor
{
    /**
     * @param string $input
     * @param Container $container
     * @return string
     * @throws ModelException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public static function process(string $input, Container $container): string
    {
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);

        // 復号化
        $decrypted = $encryptor->decrypt($input);

        // デシリアライズ
        $resultList = [];
        $requestObjectList = ModelSerializer::deserialize($decrypted);
        foreach ($requestObjectList as [$version, $requestId, $requestObject]) {
            // リクエストオブジェクト毎に処理
            $result = $dispatcher->dispatch($version, $container, $requestObject);
            if (!is_null($result)) {
                $resultList[] = [$version, $requestId, $result];
            }
        }

        // 結果をシリアライズ
        $resultBinary = ModelSerializer::serialize($resultList);

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }
}