<?php


namespace Gustav\App\Controller;

use Gustav\Common\Adapter\MySQLInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * MySQLを使うサンプルコントローラー
 * Class MySQLController
 * @package Gustav\App\Controller
 */
class MySQLController
{
    public function get($number, ResponseInterface $response, MySQLInterface $mysql): ResponseInterface
    {
        $response->getBody()->write("MySQL, ${number}!");

        return $response;
    }

}