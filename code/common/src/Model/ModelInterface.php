<?php


namespace Gustav\Common\Model;


use Google\FlatBuffers\ByteBuffer;

/**
 * Interface ModelInterface
 * @package Gustav\Common\Model
 */
interface ModelInterface extends ModelSerializable
{
    /**
     * 識別コードの取得
     * @return string
     */
    public static function chunkId(): string;

    /**
     * 現在のフォーマットバージョンを返す (1..255)
     * @return int
     */
    public static function formatVersion(): int;

    /**
     * デシリアル化
     * @param int $version
     * @param ByteBuffer $buffer
     * @return ModelInterface
     */
    public static function deserialize(int $version, ByteBuffer $buffer): ModelInterface;
}
