<?php


namespace Gustav\Common\Network;

use Gustav\Common\Operation\Time;

/**
 * Class AccessTokenManager
 * @package Gustav\Common\Network
 */
class AccessTokenManager implements AccessTokenManagerInterface
{
    const CRYPT_KEY       = '6518dc1174e3ddb1e02560db141c5d57';
    const OPENSSL_CRYPT_ALGORITHM = 'AES-128-CBC';
    const ACCESS_TOKEN_LIFETIME = 3600; // AccessTokenの消費期限

    /** @var string|null */
    private static $cryptKey = null;

    /**
     * @return string
     */
    private static function cryptKey()
    {
        if (is_null(self::$cryptKey)) {
            self::$cryptKey = hex2bin(self::CRYPT_KEY);
        }
        return self::$cryptKey;
    }

    /**
     * @inheritDoc
     */
    public function createToken(int $userId): string
    {
        // トークンの元データ
        $vector = openssl_random_pseudo_bytes(16);
        $serialized = igbinary_serialize([$userId, $this->getExpiredTime(), $vector]);

        // トークンはサーバ側で一致するかどうかの判定をするので、改竄チェック用のハッシュは入れていない(必要なさげ)

        return base64_encode($this->encrypt($serialized, $vector));
    }

    /**
     * @inheritDoc
     */
    public function getInformation(string $token): array
    {
        $serialized = $this->decrypt(base64_decode($token));

        /** @noinspection PhpUnusedLocalVariableInspection */
        list($userId, $expiredAt, $_) = igbinary_unserialize($serialized);

        return [$userId, $expiredAt];
    }

    /**
     * @inheritDoc
     */
    public function getExpiredTime(): int
    {
        return (int)(Time::now() + self::ACCESS_TOKEN_LIFETIME);
    }

    /**
     * @param string $raw
     * @param string $vector
     * @return string
     */
    protected function encrypt(string $raw, string $vector): string
    {
        // 秘密キー
        $key = self::cryptKey();

        // AES_CBC $packed $key, $iv
        // 暗号化
        $encrypted = openssl_encrypt($raw, self::OPENSSL_CRYPT_ALGORITHM, $key, true, $vector);

        // 合成して返す(
        return $encrypted . str_rot13($vector);
    }

    /**
     * @param string $token
     * @return string
     */
    protected function decrypt(string $token): string
    {
        $len = strlen($token);
        $encrypted = substr($token, 0, $len - 16);
        $vector = str_rot13(substr($token, -16));

        // 秘密キー
        $key = self::cryptKey();

        return openssl_decrypt($encrypted, self::OPENSSL_CRYPT_ALGORITHM, $key, true, $vector);
    }
}