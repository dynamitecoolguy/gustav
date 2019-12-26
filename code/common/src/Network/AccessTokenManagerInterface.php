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
     * @return string
     */
    public function createToken(int $userId): string;

    /**
     * @param string $token
     * @return int[]           (ユーザID, 有効期限)
     */
    public function getInformation(string $token): array;

    /**
     * AccessTokenの有効期限
     * @return int
     */
    public function getExpiredTime(): int;
}