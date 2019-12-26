<?php


namespace Gustav\Common\Network;

use PHPUnit\Framework\TestCase;


class KeyOperatorTest extends TestCase
{
    /**
     * @test
     */
    public function createKeys(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $this->assertEquals(2, sizeof($keys));
    }

    /**
     * @test
     */
    public function privateToPublic(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $encrypted = $op->encryptPrivate($original, $keys[0]);
        $decrypted = $op->decryptPublic($encrypted, $keys[1]);

        $this->assertEquals($original, $decrypted);
    }

    /**
     * @test
     */
    public function publicToPrivate(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $encrypted = $op->encryptPublic($original, $keys[1]);
        $decrypted = $op->decryptPrivate($encrypted, $keys[0]);

        $this->assertEquals($original, $decrypted);
    }

    /**
     * @test
     */
    public function privateToPrivate(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $encrypted = $op->encryptPrivate($original, $keys[0]);
        $decrypted = $op->decryptPrivate($encrypted, $keys[0]);

        $this->assertNotEquals($original, $decrypted);
    }

    /**
     * @test
     */
    public function publicToPublic(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $encrypted = $op->encryptPublic($original, $keys[1]);
        $decrypted = $op->decryptPublic($encrypted, $keys[1]);

        $this->assertNotEquals($original, $decrypted);
    }

    /**
     * @test
     */
    public function privateUsePublicKey(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $null = $op->encryptPrivate($original, $keys[1]);

        $this->assertNull($null);
    }

    /**
     * @test
     */
    public function publicUsePrivateKey(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $null = $op->encryptPublic($original, $keys[0]);

        $this->assertNull($null);
    }

    /**
     * @test
     */
    public function pem2der(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $privateKeyPem = $keys[0];
        $privateKeyDer = $op->pem2der($privateKeyPem);
        $publicKeyPem = $keys[1];
        $publicKeyDer = $op->pem2der($publicKeyPem);

        $this->assertEquals($privateKeyPem, $op->der2pem('PRIVATE', $privateKeyDer));

        $original = "123\u5555\n";
        $this->assertEquals($original, $op->decryptPublic($op->encryptPrivate($original, $privateKeyDer), $publicKeyDer));
        $this->assertEquals($original, $op->decryptPrivate($op->encryptPublic($original, $publicKeyDer), $privateKeyDer));
    }

    /**
     * @test
     */
    public function exchange(): void
    {
        $op = new KeyOperator();
        $keys = $op->createKeys();

        $original = 'hogehogehoge';

        $encrypted1 = base64_encode($op->encryptPrivate($original, $keys[0]));
        $encrypted2 = base64_encode($op->encryptPrivate($encrypted1, $keys[0]));
        $decrypted1 = $op->decryptPublic(base64_decode($encrypted2), $keys[1]);
        $decrypted2 = $op->decryptPublic(base64_decode($decrypted1), $keys[1]);

        $this->assertEquals($original, $decrypted2);
    }

}