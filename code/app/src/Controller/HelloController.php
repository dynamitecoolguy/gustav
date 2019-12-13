<?php

namespace Gustav\App\Controller;

use Gustav\Common\Adapter\DynamoDbInterface;
use Gustav\Common\Adapter\PgSQLInterface;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Adapter\S3Interface;
use Gustav\Common\Config\ApplicationConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Gustav\Common\Adapter\MySQLInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Slim\Interfaces\RouteInterface;
use Slim\Psr7\Environment;

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

    public function mysql($number, ResponseInterface $response, MySQLInterface $mysql): ResponseInterface
    {
        $response->getBody()->write("MySQL, ${number}!");

        $pdo = $mysql->getPDO();

        return $response;
    }

    public function pgsql(ResponseInterface $response, PgSQLInterface $pgsql): ResponseInterface
    {
        $response->getBody()->write("PgSQL");

        $pdo = $pgsql->getPDO();

        return $response;
    }

    public function redis(ResponseInterface $response, ApplicationConfigInterface $config, RedisInterface $redis): ResponseInterface
    {
        $response->getBody()->write("Redis");

        $connector = $redis->getRedis();

        return $response;
    }

    public function dynamo(ResponseInterface $response, DynamoDbInterface $dynamoDb): ResponseInterface
    {
        $response->getBody()->write("DynamoDb");

        $dynamoClient = $dynamoDb->getClient();

        return $response;

    }

    public function s3(ResponseInterface $response, S3Interface $s3, $from, $to): ResponseInterface
    {
        $response->getBody()->write("s3");

        $s3client = $s3->getClient();

        return $response;
    }
}
