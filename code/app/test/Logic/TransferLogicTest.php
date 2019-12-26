<?php


namespace Gustav\App\Logic;


use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;

class TransferLogicTest extends LogicBase
{
    private static $userId;

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
    }

    /**
     * @test
     * @throws ModelException
     */
    public function get(): void
    {
        $request = new TransferCodeModel([
        ]);

        /** @var TransferCodeModel $result */
        $result = self::getDispatcher()->dispatch(
            self::$userId,
            self::$container,
            new Pack(TransferLogic::GET_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(TransferCodeModel::class, $result);
    }
}