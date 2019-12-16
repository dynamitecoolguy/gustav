<?php


namespace Gustav\Common\Operation;

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

}