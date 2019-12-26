<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\AbstractModel;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\Dx\Registration;

/**
 * 登録ユーザを表すモデル
 * Class RegistrationModel
 * @package Gustav\App\Model
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method string getOpenId()
 * @method void setOpenId(string $openId)
 * @method string getNote()
 * @method void setNote(string $note)
 * @method string getPublicKey()
 * @method void setPublicKey(string $publicKey)
 */
class RegistrationModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
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

    /** @var string 公開鍵 */
    private $publicKey = '';
    const PUBLIC_KEY = 'publicKey';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $registration = Registration::getRootAsRegistration($buffer);

        return new static([
            self::USER_ID => $registration->getUserId(),
            self::OPEN_ID => $registration->getOpenId(),
            self::NOTE => $registration->getNote(),
            self::PUBLIC_KEY => $registration->getPublicKey()
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
            self::PUBLIC_KEY => $primitives[3]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $openId = $builder->createString($this->openId);
        $note = $builder->createString($this->note);
        $publicKey = $builder->createString($this->publicKey);
        Registration::startRegistration($builder);
        Registration::addUserId($builder, $this->userId);
        Registration::addOpenId($builder, $openId);
        Registration::addNote($builder, $note);
        Registration::addPublicKey($builder, $publicKey);
        return Registration::endRegistration($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->openId, $this->note, $this->publicKey];
    }
}