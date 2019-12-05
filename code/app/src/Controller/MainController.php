<?php


namespace Gustav\App\Controller;


use DI\Container;
use Gustav\Common\Processor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MainController
{
    public function post(
        ServerRequestInterface $request,
        Container $container,
        ResponseInterface $response): ResponseInterface
    {
        // 入力body
        $content = $request->getBody()->getContents();

        // 処理結果を出力
        $response->getBody()->write(Processor::process($content, $container));

        return $response;
    }

}