<?php


namespace Gustav\Common\Network;

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
        $t = time();
        for ($i = 0; $i < 100; $i++) {
            $userId = 10000 + $i;
            $expiredAt = $t + 600 + $i;

            $tokens[] = $atm->createToken($userId, $expiredAt);
            $compared[] = [$userId, $expiredAt];
        }
        $comparing = [];
        for ($i = 0; $i < 100; $i++) {
            $comparing[] = $atm->getInformation($tokens[$i]);
        }

        $this->assertEquals($compared, $comparing);
    }
}