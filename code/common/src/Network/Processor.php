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
     * @param AccessTokenManagerInterface $accessTokenManager
     * @return string
     * @throws NetworkException
     * @throws ModelException
     */
    public static function process(
        string $input,
        ContainerInterface $container,
        DispatcherInterface $dispatcher,
        BinaryEncryptorInterface $encryptor,
        ModelSerializerInterface $serializer,
        AccessTokenManagerInterface $accessTokenManager
    ): string
    {
        // 復号化
        $decrypted = $encryptor->decrypt($input);

        // デシリアライズ
        $requestParcel = $serializer->deserialize($decrypted);
        $requestToken = $requestParcel->getToken();

        $userId = null;

        // リクエストオブジェクト毎に処理
        $resultList = [];
        $tokenChecked = false;
        foreach ($requestParcel->getPackList() as $requestPack) {
            // トークンが必要ならばトークンをチェックする
            if ($dispatcher->isTokenRequired($requestPack)) {
                if (!$tokenChecked) {
                    $userId = self::checkToken($accessTokenManager, $requestToken);
                    $tokenChecked = true;
                }
            }

            $resultModel = $dispatcher->dispatch($userId, $container, $requestPack);
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
        $resultToken = $tokenChecked ? self::nextToken($accessTokenManager, $userId) : '';

        // 結果をシリアライズ
        $resultBinary = $serializer->serialize(new Parcel($resultToken, $resultList));

        // 暗号化
        return $encryptor->encrypt($resultBinary);
    }

    /**
     * @param AccessTokenManagerInterface $accessTokenManager
     * @param string $token
     * @return string
     * @throws NetworkException
     */
    private static function checkToken(AccessTokenManagerInterface $accessTokenManager, string $token): string
    {
        // トークン内のuserIdを取得
        /** @noinspection PhpUnusedLocalVariableInspection */
        list($userId, $expiredAt) = $accessTokenManager->getInformation($token);

        if (is_null($userId)) { // トークンが不正
            throw new NetworkException('Token is illegal format', NetworkException::INVALID_TOKEN);
        }

        return $userId;
    }

    /**
     * @param AccessTokenManagerInterface $accessTokenManager
     * @param int $userId
     * @return string
     */
    private static function nextToken(AccessTokenManagerInterface $accessTokenManager, int $userId): string
    {
        return $accessTokenManager->createToken($userId);
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
        if ($userId === '') {
            $userId = null;
        }

        // リクエストオブジェクト毎に処理
        $resultList = [];
        foreach ($requestParcel->getPackList() as $requestPack) {
            $resultModel = $dispatcher->dispatch($userId, $container, $requestPack);
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