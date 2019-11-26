<?php


namespace Gustav\Common\Operation;


use Gustav\Common\Exception\FormatException;
use PHPUnit\Framework\TestCase;

class BinaryEncryptorTest extends TestCase
{
    /**
     * @test
     * @throws FormatException
     */
    public function emptyData(): void
    {
        $this->compare('');
    }

    /**
     * @test
     * @throws FormatException
     */
    public function shortData(): void
    {
        $this->compare('123');
    }

    /**
     * @test
     * @throws FormatException
     */
    public function alphabetData(): void
    {
        $this->compare('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    /**
     * @test
     * @throws FormatException
     */
    public function binaryData(): void
    {
        $this->compare(igbinary_serialize([123, 'abc', ['漢字', 0xc0ffee11]]));
    }

    /**
     * @test
     * @throws FormatException
     */
    public function illegalData(): void
    {
        $this->expectException(FormatException::class);

        $encryptor = new BinaryEncryptor();
        $decrypted = $encryptor->decrypt('hogehoge');
    }

    /**
     * @test
     */
    public function changedData(): void
    {
        $encryptor = new BinaryEncryptor();
        $encrypted = $encryptor->encrypt('FooBarqwertyuiopasdfghjklzxcvbnm!$%&()-=^~[]{}@:*;+,.<>/?_');
        $exceptionCounter = 0;
        for ($i = 0; $i < strlen($encrypted); $i++) {
            $c = $encrypted[$i];
            try {
                $encrypted[$i] = chr(ord($encrypted[$i]) + 1);
                $encryptor->decrypt($encrypted);
            } catch (FormatException $e) {
                $exceptionCounter++;
            }
            $encrypted[$i] = $c;
        }
        $this->assertEquals(strlen($encrypted), $exceptionCounter);
    }

    /**
     * @param $original
     * @throws FormatException
     */
    private function compare($original): void
    {
        $encryptor = new BinaryEncryptor();
        $encrypted = $encryptor->encrypt($original);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }
}
