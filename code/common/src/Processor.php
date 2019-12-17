<?php

namespace Gustav\Common;


use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Exception\FormatException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
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
     * @throws ModelException
     * @throws FormatException
     */
    public static function process(string $input, Container $container): string
    {
        // Containerからデータ処理に使用するオブジェクトを取得する
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);
        $serializer = $container->get(ModelSerializerInterface::class);

        // 復号化
        $decrypted = $encryptor->decrypt($input);

        // デシリアライズ
        $requestParcel = $serializer->deserialize($decrypted);
        $requestToken = $requestParcel->getToken();

        // リクエストオブジェクト毎に処理
        $resultList = [];
        foreach ($requestParcel->getPackList() as $requestObject) {
            $result = $dispatcher->dispatch($container, $requestObject);
            if (!is_null($result)) {
                $resultList[] = new Pack(
                    $requestObject->getPackType(),
                    $requestObject->getVersion(),
                    $requestObject->getRequestId(),
                    $result
                );
            }
        }

        // 結果をシリアライズ
        $resultToken = $requestToken;
        $resultBinary = $serializer->serialize(new Parcel($resultToken, $resultList));

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }
}