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
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method string getOpenId()
 * @method void setOpenid(string $openId)
 * @method string getNote()
 * @method void setNote(string $note)
 * @method string getPrivateKey()
 * @method void setPrivateKey(string $privateKey)
 * @method string getPublicKey()
 * @method void setPublicKey(string $publicKey)
 */
class IdentificationModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
{
    /** @var int 内部用ユーザID */
    private $userId = 0;
    const USER_ID = 'userId';

    /** @var string 公開用ID */
    private $openId = '';
    const OPEN_ID = 'openId';

    /** @var string ノート */
    private $note = '';
    const NOTE = 'note';

    /** @var string 秘密鍵 */
    private $privateKey = '';
    const PRIVATE_KEY = 'privateKey';

    /** @var string 公開鍵 */
    private $publicKey = '';
    const PUBLIC_KEY = 'publicKey';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $identification = Identification::getRootAsIdentification($buffer);

        return new static([
            self::USER_ID => $identification->getUserId(),
            self::OPEN_ID => $identification->getOpenId(),
            self::NOTE => $identification->getNote(),
            self::PRIVATE_KEY => $identification->getPrivateKey(),
            self::PUBLIC_KEY => $identification->getPublicKey()
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
            self::NOTE => $primitives[2],
            self::PRIVATE_KEY => $primitives[3],
            self::PUBLIC_KEY => $primitives[4]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $openId = $builder->createString($this->openId);
        $note = $builder->createString($this->note);
        $privateKey = $builder->createString($this->privateKey);
        $publicKey = $builder->createString($this->publicKey);
        Identification::startIdentification($builder);
        Identification::addUserId($builder, $this->userId);
        Identification::addOpenId($builder, $openId);
        Identification::addNote($builder, $note);
        Identification::addPrivateKey($builder, $privateKey);
        Identification::addPublicKey($builder, $publicKey);
        return Identification::endIdentification($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->openId, $this->note, $this->privateKey, $this->publicKey];
    }
}