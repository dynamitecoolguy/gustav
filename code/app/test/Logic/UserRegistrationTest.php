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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Gustav\Common\Exception\GustavException
     */
    public function registration(): void
    {
        $request = new IdentificationModel([
            IdentificationModel::NOTE => 'hogehoge'
        ]);
        $result = self::getDispatcher()->dispatch(self::$container, new Pack('REG', 1, 'req', $request));

        $this->assertInstanceOf(IdentificationModel::class, $result);
    }
}