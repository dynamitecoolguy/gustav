<?php


namespace Gustav\App\Controller;


use DI\Container;
use Gustav\App\Dispatcher;
use Gustav\Common\Model\ModelSerializer;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MainController
{
    public function post(
        ServerRequestInterface $request,
        BinaryEncryptorInterface $encryptor,
        Container $container,
        ResponseInterface $response): ResponseInterface
    {
        // リクエストボディ
        $content = $request->getBody()->getContents();

        // 復号化
        $decrypted = $encryptor->decrypt($content);

        // デシリアライズ
        $resultList = [];
        $requestObjectList = ModelSerializer::deserialize($decrypted);
        foreach ($requestObjectList as [$requestId, $requestObject]) {
            // リクエストオブジェクト毎に処理
            $result = Dispatcher::dispatch($requestObject);
            if (!is_null($result)) {
                $resultList[] = [$requestId, $result];
            }
        }

        // 結果をシリアライズ
        $resultBinary = ModelSerializer::serialize($resultList);

        // 暗号化
        $encrypted = $encryptor->encrypt($resultBinary);

        // 結果を返す
        $response->getBody()->write($encrypted);

        return $response;
    }

}