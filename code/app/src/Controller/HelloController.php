<?php

namespace Gustav\App\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * サンプルコントローラ
 * @package Gustav\App\Controller
 */
class HelloController
{
    public function hello($who, ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write("Hello, ${who}!");

        return $response;
    }
}
