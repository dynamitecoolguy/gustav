<?php


namespace Gustav\App\Logic;

use Gustav\App\Model\RegistrationModel;
use Gustav\App\Operation\UserIdConverter;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Exception\OperationException;
use Gustav\Common\Model\Pack;
use Gustav\Common\Operation\MaximumLengthSequence;

class RegistrationLogicTest extends LogicBase
{
    /**
     * @test
     * @throws ModelException
     * @throws OperationException
     */
    public function register(): void
    {
        $request = new RegistrationModel([
            RegistrationModel::NOTE => 'hogehoge'
        ]);
        /** @var RegistrationModel $result */
        $result = self::getDispatcher()->dispatch(
            null,
            self::$container,
            new Pack(RegistrationLogic::REGISTER_ACTION, 1, 'req', $request)
        );

        $this->assertInstanceOf(RegistrationModel::class, $result);

        $userId = $result->getUserId();
        $openId = $result->getOpenId();
        $note = $result->getNote();
        $publicKey = $result->getPublicKey();

        $this->assertEquals('hogehoge', $note);
        $this->assertGreaterThan(0, $userId);

        MaximumLengthSequence::setParameter(UserIdConverter::OPEN_ID_P, UserIdConverter::OPEN_ID_Q, UserIdConverter::OPEN_ID_INIT_VALUE);
        $openIdValue = (new MaximumLengthSequence($userId))->getValue();
        $this->assertEquals(substr('000000000' . $openIdValue, -10, 10), $openId);
        MaximumLengthSequence::resetParameter();

        $this->assertStringContainsString('PUBLIC KEY', $publicKey);
    }
}