<?php


namespace Gustav\Common\Data;

/**
 * バイナリの暗号化/復号化
 * Interface BinaryEncryptorInterface
 * @package Gustav\Common\Network
 */
interface BinaryEncryptorInterface
{
    /**
     * 暗号化
     * @param string $raw
     * @return string
     */
    public function encrypt(string $raw): string;

    /**
     * 復号化
     * @param string $encoded
     * @return string
     */
    public function decrypt(string $encoded): string;
}