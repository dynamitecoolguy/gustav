<?php


namespace Gustav\App\Controller;


use DI\Container;
use Gustav\App\Dispatcher;
use Gustav\App\DispatcherInterface;
use Gustav\Common\Model\ModelSerializer;
use Gustav\Common\Operation\BinaryEncryptorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Polyfill\Util\Binary;

class MainController
{
    public function post(
        ServerRequestInterface $request,
        Container $container,
        ResponseInterface $response): ResponseInterface
    {
        $dispatcher = $container->get(DispatcherInterface::class);
        $encryptor = $container->get(BinaryEncryptorInterface::class);

        // リクエストボディ
        $content = $request->getBody()->getContents();

        // 復号化
        $decrypted = $encryptor->decrypt($content);

        // デシリアライズ
        $resultList = [];
        $requestObjectList = ModelSerializer::deserialize($decrypted);
        foreach ($requestObjectList as [$requestId, $requestObject]) {
            // リクエストオブジェクト毎に処理
            $result = $dispatcher->dispatch($container, $requestObject);
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