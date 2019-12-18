<?php


namespace Gustav\Common\Network;

/**
 * Interface KeyOperatorInterface
 * @package Gustav\Common\Operation
 */
interface KeyOperatorInterface
{
    /**
     * 秘密鍵と公開鍵の作成
     * @return string[]
     */
    public function createKeys(): array;

    /**
     * 公開鍵による暗号化
     * @param string $data
     * @param string $key
     * @return string|null
     */
    public function encryptPublic(string $data, string $key): ?string;

    /**
     * 公開鍵による復号化
     * @param string $data
     * @param string $key
     * @return string|null
     */
    public function decryptPublic(string $data, string $key): ?string;

    /**
     * 秘密鍵による暗号化
     * @param string $data
     * @param string $key
     * @return string|null
     */
    public function encryptPrivate(string $data, string $key): ?string;

    /**
     * 秘密鍵による復号化
     * @param string $data
     * @param string $key
     * @return string|null
     */
    public function decryptPrivate(string $data, string $key): ?string;
}