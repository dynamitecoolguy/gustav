<?php
// automatically generated by the FlatBuffers compiler, do not modify

namespace Gustav\Dx;

use \Google\FlatBuffers\Struct;
use \Google\FlatBuffers\Table;
use \Google\FlatBuffers\ByteBuffer;
use \Google\FlatBuffers\FlatBufferBuilder;

/// データ移管
class TransferCode extends Table
{
    /**
     * @param ByteBuffer $bb
     * @return TransferCode
     */
    public static function getRootAsTransferCode(ByteBuffer $bb)
    {
        $obj = new TransferCode();
        return ($obj->init($bb->getInt($bb->getPosition()) + $bb->getPosition(), $bb));
    }

    /**
     * @param int $_i offset
     * @param ByteBuffer $_bb
     * @return TransferCode
     **/
    public function init($_i, ByteBuffer $_bb)
    {
        $this->bb_pos = $_i;
        $this->bb = $_bb;
        return $this;
    }

    public function getPassword()
    {
        $o = $this->__offset(4);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    public function getTransferCode()
    {
        $o = $this->__offset(6);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return void
     */
    public static function startTransferCode(FlatBufferBuilder $builder)
    {
        $builder->StartObject(2);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return TransferCode
     */
    public static function createTransferCode(FlatBufferBuilder $builder, $password, $transfer_code)
    {
        $builder->startObject(2);
        self::addPassword($builder, $password);
        self::addTransferCode($builder, $transfer_code);
        $o = $builder->endObject();
        return $o;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addPassword(FlatBufferBuilder $builder, $password)
    {
        $builder->addOffsetX(0, $password, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addTransferCode(FlatBufferBuilder $builder, $transferCode)
    {
        $builder->addOffsetX(1, $transferCode, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return int table offset
     */
    public static function endTransferCode(FlatBufferBuilder $builder)
    {
        $o = $builder->endObject();
        return $o;
    }

    public static function finishTransferCodeBuffer(FlatBufferBuilder $builder, $offset)
    {
        $builder->finish($offset);
    }
}
