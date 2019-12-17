<?php


namespace Gustav\App\Logic;

use Gustav\App\Model\IdentificationModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;

class UserRegistrationTest extends LogicBase
{
    /**
     * @test
     * @throws ModelException
     */
    public function registration(): void
    {
        $request = new IdentificationModel([
            IdentificationModel::NOTE => 'hogehoge'
        ]);
        $result = self::$dispatcher->dispatch(self::$container, new Pack('REG', 1, 'req', $request));

        $this->assertInstanceOf(IdentificationModel::class, $result);
    }
}