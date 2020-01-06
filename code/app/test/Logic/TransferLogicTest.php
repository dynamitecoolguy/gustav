<?php


namespace Gustav\App\Logic;


use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\ResultModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\Pack;

class TransferLogicTest extends LogicBase
{
    private static $userId;
    private static $transferCode;

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
        self::$transferCode = $result->getTransferCode();
    }

    /**
     * @test
     * @throws ModelException
     */
    public function setPassword(): void
    {
        $request = new TransferCodeModel([
            TransferCodeModel::PASSWORD => 'hogehoge'
        ]);

        /** @var ResultModel $result */
        $result = self::getDispatcher()->dispatch(
            self::$userId,
            self::$container,
            new Pack(TransferLogic::SET_PASSWORD_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(ResultModel::class, $result);
    }

    /**
     * @test
     * @throws ModelException
     */
    public function execute(): void
    {
        $request = new TransferCodeModel([
            TransferCodeModel::TRANSFER_CODE => self::$transferCode,
            TransferCodeModel::PASSWORD => 'hogehoge'
        ]);

        /** @var RegistrationModel $result */
        $result = self::getDispatcher()->dispatch(
            null,
            self::$container,
            new Pack(TransferLogic::EXECUTE_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(RegistrationModel::class, $result);
        $this->assertEquals(self::$userId, $result->getUserId());
    }
}