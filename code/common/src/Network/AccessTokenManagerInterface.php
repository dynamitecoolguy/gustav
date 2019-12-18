<?php


namespace Gustav\Common\Network;

/**
 * Interface AccessTokenManagerInterface
 * @package Gustav\Common\Network
 */
interface AccessTokenManagerInterface
{
    /**
     * @param int $userId
     * @param int $expiredAt
     * @return string
     */
    public function createToken(int $userId, int $expiredAt): string;

    /**
     * @param string $token
     * @return int[]           (ユーザID, 有効期限)
     */
    public function getInformation(string $token): array;
}