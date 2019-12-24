<?php


namespace Gustav\App\Model;


use Google\FlatBuffers\ByteBuffer;
use Google\FlatBuffers\FlatbufferBuilder;
use Gustav\Common\Model\AbstractModel;
use Gustav\Common\Model\FlatBuffers\FlatBuffersSerializable;
use Gustav\Common\Model\Primitive\PrimitiveSerializable;
use Gustav\Dx\Authentication;

/**
 * ユーザ認証を表すモデル
 * Class AuthenticationModel
 * @package Gustav\App\Model
 * @method int getUserId()
 * @method void setUserId(int $userId)
 * @method string getSecret()
 * @method void setSecret(string $secret)
 * @method string getAccessToken()
 * @method void setAccessToken(string $accessToken)
 */
class AuthenticationModel extends AbstractModel implements FlatBuffersSerializable, PrimitiveSerializable
{
    /** @var int 内部用ユーザID */
    private $userId = 0;
    const USER_ID = 'userId';

    /** @var string 交換用鍵 */
    private $secret = '';
    const SECRET = 'secret';

    /** @var string アクセス用トークン */
    private $accessToken = '';
    const ACCESS_TOKEN = 'accessToken';

    /**
     * @inheritDoc
     */
    public static function deserializeFlatBuffers(int $version, ByteBuffer $buffer): FlatBuffersSerializable
    {
        $authentication = Authentication::getRootAsAuthentication($buffer);

        return new static([
            self::USER_ID => $authentication->getUserId(),
            self::SECRET => $authentication->getSecret(),
            self::ACCESS_TOKEN => $authentication->getAccessToken()
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function deserializePrimitive(int $version, array $primitives): PrimitiveSerializable
    {
        return new static([
            self::USER_ID => $primitives[0],
            self::SECRET => $primitives[1],
            self::ACCESS_TOKEN => $primitives[2]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function serializeFlatBuffers(FlatbufferBuilder &$builder): int
    {
        $secret = $builder->createString($this->secret);
        $accessToken = $builder->createString($this->accessToken);
        Authentication::startAuthentication($builder);
        Authentication::addUserId($builder, $this->userId);
        Authentication::addSecret($builder, $secret);
        Authentication::addAccessToken($builder, $accessToken);
        return Authentication::endAuthentication($builder);
    }

    /**
     * @inheritDoc
     */
    public function serializePrimitive(): array
    {
        return [$this->userId, $this->secret, $this->accessToken];
    }
}