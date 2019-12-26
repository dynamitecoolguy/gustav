<?php


namespace Gustav\App\Model;

use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\AbstractModel;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\Dx\TransferCode;

/**
 * データ移管を表すモデル
 * Class TransferCodeModel
 * @package Gustav\App\Model
 * @method string getTransferCode()
 * @method void setTransferCode(string $transferCode)
 * @method string getPassword()
 * @method void setPassword(string $password)
 * @method int getResult()
 * @method void setResult(int $result)
 */
class TransferCodeModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
{
    /** @var string 移管コード */
    private $transferCode = '';
    const TRANSFER_CODE = 'transferCode';

    /** @var string パスワード */
    private $password = '';
    const PASSWORD = 'password';

    /** @var int アクション結果 */
    private $result = 0;
    const RESULT = 'result';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $transferCode = TransferCode::getRootAsTransferCode($buffer);

        return new static([
            self::PASSWORD => $transferCode->getPassword(),
            self::TRANSFER_CODE => $transferCode->getTransferCode(),
            self::RESULT => $transferCode->getResult()
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        return new static([
            self::PASSWORD => $primitives[0],
            self::TRANSFER_CODE => $primitives[1],
            self::RESULT => $primitives[2]
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
        TransferCode::addPassword($builder, $password);
        TransferCode::addTransferCode($builder, $transferCode);
        TransferCode::addResult($builder, $this->result);
        return TransferCode::endTransferCode($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->password, $this->transferCode, $this->result];
    }
}