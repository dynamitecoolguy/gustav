<?php

namespace Gustav\App\Controller;

use DI\Container;
use \Exception;
use Gustav\Common\Processor;
use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\ParameterResolver\ResolverChain;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class MainController
 * @package Gustav\App\Controller
 */
class MainController
{
    /**
     * @param ServerRequestInterface $request
     * @param Container $container
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @uses Processor::process()
     */
    public function post(
        ServerRequestInterface $request,
        Container $container,
        ResponseInterface $response): ResponseInterface
    {
        try {
            // リクエストのボディ部の取得
            $content = $request->getBody()->getContents();

            // Processing
            $invoker = new Invoker(
                new ResolverChain([
                    new AssociativeArrayResolver(),
                    new TypeHintContainerResolver($container)
                ]),
                $container);
            $outputData = $invoker->call(
                [Processor::class, 'process'],
                ['input' => $content]
            );

            // 処理結果を出力
            $response->getBody()->write($outputData);
        } catch (Exception $e) {
            // 余分な情報を与えない
            $response->withStatus(500);
            // TODO: ログ出力
        }
        return $response;
    }
}