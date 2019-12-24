<?php

namespace Gustav\Common\Network;


use Gustav\Common\Exception\NetworkException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\Parcel;
use Gustav\Common\Model\ModelSerializerInterface;
use Gustav\Common\Model\Primitive\JsonSerializer;
use Psr\Container\ContainerInterface;

/**
 * Class Processor
 * @package Gustav\Common
 */
class Processor
{
    /**
     * @param string $input
     * @param ContainerInterface $container
     * @param DispatcherInterface $dispatcher
     * @param BinaryEncryptorInterface $encryptor
     * @param ModelSerializerInterface $serializer
     * @return string
     * @throws NetworkException
     * @throws ModelException
     */
    public static function process(
        string $input,
        ContainerInterface $container,
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
        $tokenChecked = false;
        foreach ($requestParcel->getPackList() as $requestPack) {
            // トークンが必要ならばトークンをチェックする
            if ($dispatcher->isTokenRequired($requestPack)) {
                if (!$tokenChecked) {
                    self::checkToken($requestToken);
                    $tokenChecked = true;
                }
            }

            $resultModel = $dispatcher->dispatch($container, $requestPack);
            if (!is_null($resultModel)) {
                $resultList[] = new Pack(
                    $requestPack->getPackType(),
                    $requestPack->getVersion(),
                    $requestPack->getRequestId(),
                    $resultModel
                );
            }
        }

        // トークンを使ったら、次のトークンを用意
        $resultToken = $tokenChecked ? self::nextToken() : '';

        // 結果をシリアライズ
        $resultBinary = $serializer->serialize(new Parcel($resultToken, $resultList));

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }

    private static function checkToken(string $token): void
    {
        // TODO: トークンのチェック
    }

    private static function nextToken(): string
    {
        // TODO: 次のトークン
        return 'hogehoge';
    }

    /**
     * まったく暗号化されていない通信 (主にデバグ用途)
     * @param string $input
     * @param ContainerInterface $container
     * @param DispatcherInterface $dispatcher
     * @return string
     * @throws ModelException
     */
    public static function processUnsealed(
        string $input,
        ContainerInterface $container,
        DispatcherInterface $dispatcher): string
    {
        // デシリアライズ
        $serializer = new JsonSerializer();

        $requestParcel = $serializer->deserialize($input);
        $userId = $requestParcel->getToken();

        // リクエストオブジェクト毎に処理
        $resultList = [];
        foreach ($requestParcel->getPackList() as $requestPack) {
            $resultModel = $dispatcher->dispatch($container, $requestPack);
            if (!is_null($resultModel)) {
                $resultList[] = new Pack(
                    $requestPack->getPackType(),
                    $requestPack->getVersion(),
                    $requestPack->getRequestId(),
                    $resultModel
                );
            }
        }

        // 結果をシリアライズ
        return $serializer->serialize(new Parcel($userId, $resultList));
    }
}