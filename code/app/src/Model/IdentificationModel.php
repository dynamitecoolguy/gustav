<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\AbstractModel;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\Dx\Identification;

/**
 * 登録
 * Class IdentificationModel
 * @package Gustav\App\Model
 */
class IdentificationModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
{
    /** @var int 内部用ユーザID */
    private $userId = 0;
    const USER_ID = 'userId';

    /** @var string 公開用ID */
    private $openId = '';
    const OPEN_ID = 'openId';

    /** @var string キャンペーンコード */
    private $campaignCode = '';
    const CAMPAIGN_CODE = 'campaignCode';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $identification = Identification::getRootAsIdentification($buffer);

        return new static([
            self::USER_ID => $identification->getUserId(),
            self::OPEN_ID => $identification->getOpenId(),
            self::CAMPAIGN_CODE => $identification->getCampaignCode()
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        return new static([
            self::USER_ID => $primitives[0],
            self::OPEN_ID => $primitives[1],
            self::CAMPAIGN_CODE => $primitives[2]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $campaignCode = $builder->createString($this->campaignCode);
        $openId = $builder->createString($this->openId);
        Identification::startIdentification($builder);
        Identification::addUserId($builder, $this->userId);
        Identification::addOpenId($builder, $openId);
        Identification::addCampaignCode($builder, $campaignCode);
        return Identification::endIdentification($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->openId, $this->campaignCode];
    }
}