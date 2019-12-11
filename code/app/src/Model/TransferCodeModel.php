<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\AbstractModel;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\Dx\TransferCode;

class TransferCodeModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
{
    /** @var int 内部用ユーザID */
    private $userId = 0;
    const USER_ID = 'userId';

    /** @var string 移管コード */
    private $transferCode = '';
    const TRANSFER_CODE = 'transferCode';

    /** @var string パスワード */
    private $password = '';
    const PASSWORD = 'password';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $transferCode = TransferCode::getRootAsTransferCode($buffer);

        return new static([
            self::USER_ID => $transferCode->getUserId(),
            self::TRANSFER_CODE => $transferCode->getTransferCode(),
            self::PASSWORD => $transferCode->getPassword()
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        return new static([
            self::USER_ID => $primitives[0],
            self::TRANSFER_CODE => $primitives[1],
            self::PASSWORD => $primitives[2]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $transferCode = $builder->createString($this->transferCode);
        $password = $builder->createString($this->password);
        TransferCode::startTransferCode($builder);
        TransferCode::addUserId($builder, $this->userId);
        TransferCode::addTransferCode($builder, $transferCode);
        TransferCode::addPassword($builder, $password);
        return TransferCode::endTransferCode($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->transferCode, $this->password];
    }
}