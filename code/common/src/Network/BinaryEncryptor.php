<?php


namespace Gustav\Common\Network;


use Gustav\Common\Exception\NetworkException;

/**
 * バイナリー列の暗号化/復号化処理
 * Class BinaryEncryptor
 * @package Gustav\Common\Operation
 */
class BinaryEncryptor implements BinaryEncryptorInterface
{
    const HASH_ALGORITHM  = 'sha256';
    const CRYPT_KEY       = '0ef71ec9cca1f45d07cae9af7f46e34f';
    const OPENSSL_CRYPT_ALGORITHM = 'AES-128-CBC';

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
     * BinaryEncryptor constructor.
     */
    public function __construct()
    {
        // do nothing
    }

    /**
     * @inheritDoc
     */
    public function encrypt(string $raw): string
    {
        // 初期ベクタ
        $initialVector = openssl_random_pseudo_bytes(16);

        // 秘密キー
        $key = self::cryptKey();

        // AES_CBC $packed $key, $iv
        // 暗号化
        $encrypted = openssl_encrypt($raw, self::OPENSSL_CRYPT_ALGORITHM, $key, true, $initialVector);

        // ハッシュキーの作成
        $hash = $this->getHashKey($encrypted, $initialVector);

        // 合成して返す
        return $this->pack($encrypted, $initialVector, $hash);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $packed): string
    {
        // BODYをコンテンツ部分、暗号の初期ベクター部分、ハッシュ部分に分割
        [$encrypted, $initialVector, $hash] = $this->unpack($packed);

        // コンテンツとハッシュを比較し、整合性を確認
        $computedHash = $this->getHashKey($encrypted, $initialVector);

        if (strcmp($hash, $computedHash) !== 0) {
            // データが改竄されている可能性がある
            throw new NetworkException('Hash is inconsistency', NetworkException::HASH_IS_INCONSISTENCY);
        }

        // 秘密キー
        $key = self::cryptKey();

        return openssl_decrypt($encrypted, self::OPENSSL_CRYPT_ALGORITHM, $key, true, $initialVector);
    }

    /**
     * 暗号化データの中からハッシュ化に使うキーを取得
     * @param string $encrypted
     * @param string $initialVector
     * @return string
     */
    protected function getHashKey(string $encrypted, string $initialVector): string
    {
        if (strlen($encrypted) >= 20) {
            $hashKey = substr($encrypted, 4, 16);
        } else {
            $hashKey = strrev($encrypted);
        }
        return hash_hmac(self::HASH_ALGORITHM, $encrypted . $initialVector, $hashKey, true);
    }

    /**
     * 暗号化されたデータ、初期ベクタ、検証用ハッシュを結合する
     * @param string $encrypted     暗号化データ
     * @param string $initialVector 初期ベクタ
     * @param string $hash          検証用ハッシュ
     * @return string
     */
    protected function pack(string $encrypted, string $initialVector, string $hash): string
    {
        return $encrypted . $initialVector . $hash;
    }

    /**
     * pack()メソッドで結合された暗号化されたデータ、初期ベクタ、検証用ハッシュを分割する
     * @param string $packed
     * @return array
     * @throws NetworkException
     */
    protected function unpack(string $packed): array
    {
        // BODYをコンテンツ部分、暗号の初期ベクター部分、ハッシュ部分に分割
        $len = strlen($packed);
        if ($len < 48) {
            // リクエスト短すぎる
            throw new NetworkException('Message is too short', NetworkException::MESSAGE_IS_TOO_SHORT);
        }
        $encrypted = substr($packed, 0, $len - 48);
        $initialVector = substr($packed, -48, 16);
        $hash = substr($packed, -32);

        return [$encrypted, $initialVector, $hash];
    }
}
