<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;

use Gustav\Common\Model\ModelInterface;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\DX\Registration as Registration;

/**
 * 登録
 * Class RegistrationModel
 * @package Gustav\App\Model
 */
class RegistrationModel implements FlatBuffersSerializable, PrimitiveSerializable, ModelInterface
{
    private $userId;

    private $openId;

    private $campaignCode;

    /**
     * @param int $version
     * @param ByteBuffer $buffer
     * @return FlatBuffersSerializable
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $registration = Registration::getRootAsRegistration($buffer);

        return new RegistrationModel(
            $registration->getUserId(),
            (int)$registration->getOpenId(),
            $registration->getCampaignCode()
        );
    }


    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        $self = new static();
        list($self->userId, $self->openId, $self->campaignCode) = $primitives;
        return $self;
    }

    /**
     * RegistrationModel constructor.
     * @param int $userId
     * @param int $openId
     * @param string $campaignCode
     */
    public function __construct(int $userId = 0, int $openId = 0, string $campaignCode = '')
    {
        $this->userId = $userId;
        $this->openId = $openId;
        $this->campaignCode = $campaignCode;
    }

    /**
     * @param FlatbufferBuilder $builder
     * @return int
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $campaignCode = $builder->createString($this->campaignCode);
        Registration::startRegistration($builder);
        Registration::addUserId($builder, $this->userId);
        Registration::addOpenId($builder, $this->openId);
        Registration::addCampaignCode($builder, $campaignCode);
        return Registration::endRegistration($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->openId, $this->campaignCode];
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