<?php


namespace Gustav\Common\Network;

use Gustav\Common\Operation\Time;
use PHPUnit\Framework\TestCase;

class AccessTokenManagerTest extends TestCase
{
    /**
     * @test
     */
    public function tokenCheck()
    {
        $atm = new AccessTokenManager();

        $tokens = [];
        $compared = [];
        for ($i = 0; $i < 100; $i++) {
            $userId = 10000 + $i;
            $expiredAt = (int)(Time::now() + AccessTokenManager::ACCESS_TOKEN_LIFETIME);

            $tokens[] = $atm->createToken($userId);
            $compared[] = [$userId, $expiredAt];
        }
        $comparing = [];
        for ($i = 0; $i < 100; $i++) {
            $comparing[] = $atm->getInformation($tokens[$i]);
        }

        $this->assertEquals($compared, $comparing);
    }
}