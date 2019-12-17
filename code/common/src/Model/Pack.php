<?php


namespace Gustav\Common\Model;

/**
 * リクエスト/レスポンスオブジェクトとその付随情報
 * Class Pack
 * @package Gustav\Common\Model
 */
class Pack
{
    /** @var string リクエスト識別子*/
    private $packType;

    /** @var int フォーマットバージョン */
    private $version;

    /** @var string リクエストID */
    private $requestId;

    /** @var ModelInterface リクエスト/レスポンスのオブジェクト */
    private $model;

    public function __construct(string $packType, int $version, string $requestId, ModelInterface $model)
    {
        $this->packType = $packType;
        $this->version = $version;
        $this->requestId = $requestId;
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getPackType(): string
    {
        return $this->packType;
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @return ModelInterface
     */
    public function getModel(): ModelInterface
    {
        return $this->model;
    }
}