<?php

namespace Gustav\Common;


use DI\Container;
use Gustav\Common\Exception\FormatException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Network\BinaryEncryptorInterface;

/**
 * Class Processor
 * @package Gustav\Common
 */
class Processor
{
    /**
     * @param string $input
     * @param Container $container
     * @param DispatcherInterface $dispatcher
     * @param BinaryEncryptorInterface $encryptor
     * @param ModelSerializerInterface $serializer
     * @return string
     * @throws FormatException
     * @throws ModelException
     */
    public static function process(
        string $input,
        Container $container,
        DispatcherInterface $dispatcher,
        BinaryEncryptorInterface $encryptor,
        ModelSerializerInterface $serializer): string
    {
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