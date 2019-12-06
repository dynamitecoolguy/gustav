<?php


namespace Gustav\Common\Model;


interface ModelSerializerInterface
{
    /**
     * オブジェクトリストからデータストリームへの変換
     * @param array $objectList   [[version(int), requestId(string), object(ModelInterface)]]
     * @return string
     */
    public function serialize(array $objectList): string;

    /**
     * @param string $stream
     * @return array  [[version(int), requestId(string), object(ModelInterface)]]
     */
    public function deserialize(string $stream): array;
}