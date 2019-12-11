<?php


namespace Gustav\Common\Model;

/**
 * Class ModelChunk
 * @package Gustav\Common\Model
 */
class ModelChunk
{
    /** @var string リクエスト識別子*/
    private $chunkId;

    /** @var int フォーマットバージョン */
    private $version;

    /** @var string リクエストID */
    private $requestId;

    /** @var ModelInterface リクエスト/レスポンスのオブジェクト */
    private $model;

    public function __construct(string $chunkId, int $version, string $requestId, ModelInterface $model)
    {
        $this->chunkId = $chunkId;
        $this->version = $version;
        $this->requestId = $requestId;
        $this->model = $model;
    }

    /**
     * @return string
     */
    public function getChunkId(): string
    {
        return $this->chunkId;
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