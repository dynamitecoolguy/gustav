<?php
// automatically generated by the FlatBuffers compiler, do not modify

namespace Gustav\Dx;

use \Google\FlatBuffers\Struct;
use \Google\FlatBuffers\Table;
use \Google\FlatBuffers\ByteBuffer;
use \Google\FlatBuffers\FlatBufferBuilder;

/// ユーザ登録
class Registration extends Table
{
    /**
     * @param ByteBuffer $bb
     * @return Registration
     */
    public static function getRootAsRegistration(ByteBuffer $bb)
    {
        $obj = new Registration();
        return ($obj->init($bb->getInt($bb->getPosition()) + $bb->getPosition(), $bb));
    }

    /**
     * @param int $_i offset
     * @param ByteBuffer $_bb
     * @return Registration
     **/
    public function init($_i, ByteBuffer $_bb)
    {
        $this->bb_pos = $_i;
        $this->bb = $_bb;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        $o = $this->__offset(4);
        return $o != 0 ? $this->bb->getInt($o + $this->bb_pos) : 0;
    }

    public function getOpenId()
    {
        $o = $this->__offset(6);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    public function getTransferCode()
    {
        $o = $this->__offset(8);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    public function getNote()
    {
        $o = $this->__offset(10);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    public function getPublicKey()
    {
        $o = $this->__offset(12);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return void
     */
    public static function startRegistration(FlatBufferBuilder $builder)
    {
        $builder->StartObject(5);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return Registration
     */
    public static function createRegistration(FlatBufferBuilder $builder, $user_id, $open_id, $transfer_code, $note, $public_key)
    {
        $builder->startObject(5);
        self::addUserId($builder, $user_id);
        self::addOpenId($builder, $open_id);
        self::addTransferCode($builder, $transfer_code);
        self::addNote($builder, $note);
        self::addPublicKey($builder, $public_key);
        $o = $builder->endObject();
        return $o;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param int
     * @return void
     */
    public static function addUserId(FlatBufferBuilder $builder, $userId)
    {
        $builder->addIntX(0, $userId, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addOpenId(FlatBufferBuilder $builder, $openId)
    {
        $builder->addOffsetX(1, $openId, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addTransferCode(FlatBufferBuilder $builder, $transferCode)
    {
        $builder->addOffsetX(2, $transferCode, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addNote(FlatBufferBuilder $builder, $note)
    {
        $builder->addOffsetX(3, $note, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addPublicKey(FlatBufferBuilder $builder, $publicKey)
    {
        $builder->addOffsetX(4, $publicKey, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return int table offset
     */
    public static function endRegistration(FlatBufferBuilder $builder)
    {
        $o = $builder->endObject();
        return $o;
    }

    public static function finishRegistrationBuffer(FlatBufferBuilder $builder, $offset)
    {
        $builder->finish($offset);
    }
}
