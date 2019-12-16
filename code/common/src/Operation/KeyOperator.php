<?php


namespace Gustav\Common\Operation;


class KeyOperator implements KeyOperatorInterface
{
    const DIGEST_ALG = 'SHA256';
    const PRIVATE_KEY_LENGTH = 1024;

    /**
     * 秘密鍵と公開鍵の作成。出力はDER形式
     * @return string[]  (PrivateKey, PublicKey)
     */
    public function createKeys(): array
    {
        $resource = openssl_pkey_new([
            'digest_alg' => self::DIGEST_ALG,
            'private_key_bits' => self::PRIVATE_KEY_LENGTH,
            'private_key_type' => OPENSSL_KEYTYPE_RSA
        ]);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        $result = [$this->pem2der($privateKey), $this->pem2der($details['key'])];

        openssl_pkey_free($resource);

        return $result;
    }

    public function encryptPublic(string $data, string $key): ?string
    {
        if ($key[0] !== '-') {
            $key = $this->der2pem('PUBLIC', $key);
        }
        if (!@openssl_public_encrypt($data, $crypted, $key, OPENSSL_PKCS1_PADDING)) {
            return null;
        }
        return $crypted;
    }

    public function decryptPublic(string $data, string $key): ?string
    {
        if ($key[0] !== '-') {
            $key = $this->der2pem('PUBLIC', $key);
        }
        if (!@openssl_public_decrypt($data, $decrypted, $key, OPENSSL_PKCS1_PADDING)) {
            return null;
        }
        return $decrypted;
    }

    public function encryptPrivate(string $data, string $key): ?string
    {
        if ($key[0] !== '-') {
            $key = $this->der2pem('PRIVATE', $key);
        }
        if (!@openssl_private_encrypt($data, $crypted, $key, OPENSSL_PKCS1_PADDING)) {
            return null;
        }
        return $crypted;
    }

    public function decryptPrivate(string $data, string $key): ?string
    {
        if ($key[0] !== '-') {
            $key = $this->der2pem('PRIVATE', $key);
        }
        if (!@openssl_private_decrypt($data, $decrypted, $key, OPENSSL_PKCS1_PADDING)) {
            return null;
        }
        return $decrypted;
    }

    /**
     * PEMからDERへの変換
     * @param string $pem
     * @return string
     */
    private function pem2der(string $pem): string
    {
        return base64_decode(preg_replace('/-----[A-Z ]*-----/', '', $pem));
    }

    /**
     * DERからPEMへの変換
     * @param string $type   'PRIVATE' or 'PUBLIC'
     * @param string $der
     * @return string
     */
    private function der2pem(string $type, string $der): string
    {
        return "-----BEGIN ${type} KEY-----\n"
            . wordwrap(base64_encode($der), 64, "\n", true)
            . "\n-----END ${type} KEY-----\n";
    }
}