<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

interface ModelSerializerInterface
{
    /**
     * オブジェクトリストからデータストリームへの変換
     * @param ModelChunk[] $objectList  データストリームへ変換するオブジェクトのリスト
     * @return string
     * @throws ModelException
     */
    public function serialize(array $objectList): string;

    /**
     * データストリームからオブジェクトリストへの変換
     * @param string $stream
     * @return ModelChunk[]
     * @throws ModelException
     */
    public function deserialize(string $stream): array;
}