<?php


namespace Gustav\App\Logic;

use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Exception\ModelException;

class UserRegistrationTest extends LogicBase
{
    /**
     * @test
     * @throws ModelException
     */
    public function registration(): void
    {
        $request = new IdentificationModel([
            IdentificationModel::CAMPAIGN_CODE => 'hogehoge'
        ]);
        $result = self::$dispatcher->dispatch(1, self::$container, $request);

        $this->assertInstanceOf(IdentificationModel::class, $result);
    }
}