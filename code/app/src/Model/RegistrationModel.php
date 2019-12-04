<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\ModelInterface;

use Gustav\DX\Registration as Registration;

/**
 * 登録
 * Class RegistrationModel
 * @package Gustav\App\Model
 */
class RegistrationModel implements ModelInterface
{
    private $userId;

    private $openId;

    private $campaignCode;

    /**
     * @param int $version
     * @param ByteBuffer $buffer
     * @return ModelInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface
    {
        $registration = Registration::getRootAsRegistration($buffer);

        return new RegistrationModel(
            $registration->getUserId(),
            (int)$registration->getOpenId(),
            $registration->getCampaignCode()
        );
    }

    /**
     * RegistrationModel constructor.
     * @param int $userId
     * @param int $openId
     * @param string $campaignCode
     */
    public function __construct(int $userId, int $openId, string $campaignCode)
    {
        $this->userId = $userId;
        $this->openId = $openId;
        $this->campaignCode = $campaignCode;
    }

    /**
     * @param FlatbufferBuilder $builder
     * @return int
     */
    public function serialize(FlatbufferBuilder &$builder): int
    {
        Registration::startRegistration($builder);
        Registration::addUserId($builder, $this->userId);
        Registration::addOpenId($builder, $this->openId);
        Registration::addCampaignCode($builder, $builder->createString($this->campaignCode));
        return Registration::endRegistration($builder);
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getOpenId(): int
    {
        return $this->openId;
    }

    /**
     * @param int $openId
     */
    public function setOpenId(int $openId): void
    {
        $this->openId = $openId;
    }

    /**
     * @return string
     */
    public function getCampaignCode(): string
    {
        return $this->campaignCode;
    }

    /**
     * @param string $campaignCode
     */
    public function setCampaignCode(string $campaignCode): void
    {
        $this->campaignCode = $campaignCode;
    }
}