<?php

namespace Gustav\Mgr\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * サンプルコントローラ
 * @package Gustav\Mgr\Controller
 */
class HelloController
{
    public function hello($who, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write("Hello, ${who}!");

        return $response;
    }
}
