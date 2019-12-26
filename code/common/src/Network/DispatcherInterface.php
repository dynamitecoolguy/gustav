<?php


namespace Gustav\Common\Network;

use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Model\ModelInterface;
use Psr\Container\ContainerInterface;

/**
 * Interface DispatcherInterface
 * @package Gustav\Common
 */
interface DispatcherInterface
{
    /**
     * @param int|null                $userId;       // 対象ユーザ
     * @param ContainerInterface      $container     // Container
     * @param Pack                    $request       // リクエストオブジェクト
     * @return ModelInterface|null                   // リザルト。必要ない場合はnull
     * @throws ModelException
     */
    public function dispatch(?int $userId, ContainerInterface $container, Pack $request): ?ModelInterface;

    /**
     * トークンが必要かどうか
     * @param Pack $requestToken
     * @return bool
     */
    public function isTokenRequired(Pack $requestToken): bool;
}