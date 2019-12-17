<?php


namespace Gustav\Common\Model;


use Gustav\Common\Exception\ModelException;

interface ModelSerializerInterface
{
    /**
     * オブジェクトリストからデータストリームへの変換
     * @param Parcel $parcel  データストリームへ変換するオブジェクトのリスト
     * @return string
     * @throws ModelException
     */
    public function serialize(Parcel $parcel): string;

    /**
     * データストリームからオブジェクトリストへの変換
     * @param string $stream
     * @return Parcel
     * @throws ModelException
     */
    public function deserialize(string $stream): Parcel;
}