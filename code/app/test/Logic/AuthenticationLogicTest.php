<?php


namespace Gustav\App\Logic;


use Gustav\App\Model\AuthenticationModel;
use Gustav\App\Model\RegistrationModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Network\AccessTokenManager;

class AuthenticationLogicTest extends LogicBase
{
    private static $userId;
    private static $publicKey;
    private static $randomBytes;

    /**
     * @beforeClass
     * @throws ModelException
     */
    public static function createUser(): void
    {
        // ユーザ登録
        $request = new RegistrationModel([
            RegistrationModel::NOTE => 'hogehoge'
        ]);
        /** @var RegistrationModel $result */
        $result = self::getDispatcher()->dispatch(
            null,
            self::$container,
            new Pack(RegistrationLogic::REGISTER_ACTION, 1, 'req', $request)
        );

        self::$userId = $result->getUserId();
        self::$publicKey = $result->getPublicKey();
    }

    /**
     * @test
     * @throws ModelException
     */
    public function request(): void
    {
        // 認証
        $request = new AuthenticationModel([
            AuthenticationModel::USER_ID => self::$userId
        ]);

        /** @var AuthenticationModel $result */
        $result = self::getDispatcher()->dispatch(
            null,
            self::$container,
            new Pack(AuthenticationLogic::REQUEST_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(AuthenticationModel::class, $result);

        $this->assertEquals(self::$userId, $result->getUserId());
        $secret = $result->getSecret();
        $this->assertNotEmpty($secret);

        $encrypted = base64_decode($secret);

        openssl_public_decrypt($encrypted, $decrypted, self::$publicKey);
        $this->assertNotEmpty($decrypted);

        self::$randomBytes = $decrypted;
    }

    /**
     * @test
     * @depends request
     * @throws ModelException
     */
    public function publish(): void
    {
        // 認証
        $request = new AuthenticationModel([
            AuthenticationModel::USER_ID => self::$userId,
            AuthenticationModel::SECRET => base64_encode(self::$randomBytes)
        ]);

        /** @var AuthenticationModel $result */
        $result = self::getDispatcher()->dispatch(
            null,
            self::$container,
            new Pack(AuthenticationLogic::PUBLISH_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(AuthenticationModel::class, $result);

        $this->assertEquals(self::$userId, $result->getUserId());
        $accessToken = $result->getAccessToken();
        $this->assertNotEmpty($accessToken);

        $accessTokenManager = new AccessTokenManager();
        list($userId, $expiredAt) = $accessTokenManager->getInformation($accessToken);

        $this->assertEquals($userId, self::$userId);
        $this->assertGreaterThan(time(), $expiredAt);
    }
}