<?php /** @noinspection ALL */

// automatically generated by the FlatBuffers compiler, do not modify

namespace Gustav\Common\Model\FlatBuffers;

use \Google\FlatBuffers\Struct;
use \Google\FlatBuffers\Table;
use \Google\FlatBuffers\ByteBuffer;
use \Google\FlatBuffers\FlatBufferBuilder;

class FlatBuffersParcel extends Table
{
    /**
     * @param ByteBuffer $bb
     * @return FlatBuffersParcel
     */
    public static function getRootAsFlatBuffersParcel(ByteBuffer $bb)
    {
        $obj = new FlatBuffersParcel();
        return ($obj->init($bb->getInt($bb->getPosition()) + $bb->getPosition(), $bb));
    }

    /**
     * @param int $_i offset
     * @param ByteBuffer $_bb
     * @return FlatBuffersParcel
     **/
    public function init($_i, ByteBuffer $_bb)
    {
        $this->bb_pos = $_i;
        $this->bb = $_bb;
        return $this;
    }

    public function getToken()
    {
        $o = $this->__offset(4);
        return $o != 0 ? $this->__string($o + $this->bb_pos) : null;
    }

    /**
     * @returnVectorOffset
     */
    public function getPack($j)
    {
        $o = $this->__offset(6);
        $obj = new FlatBuffersPack();
        return $o != 0 ? $obj->init($this->__indirect($this->__vector($o) + $j * 4), $this->bb) : null;
    }

    /**
     * @return int
     */
    public function getPackLength()
    {
        $o = $this->__offset(6);
        return $o != 0 ? $this->__vector_len($o) : 0;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return void
     */
    public static function startFlatBuffersParcel(FlatBufferBuilder $builder)
    {
        $builder->StartObject(2);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return FlatBuffersParcel
     */
    public static function createFlatBuffersParcel(FlatBufferBuilder $builder, $token, $pack)
    {
        $builder->startObject(2);
        self::addToken($builder, $token);
        self::addPack($builder, $pack);
        $o = $builder->endObject();
        return $o;
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param StringOffset
     * @return void
     */
    public static function addToken(FlatBufferBuilder $builder, $token)
    {
        $builder->addOffsetX(0, $token, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param VectorOffset
     * @return void
     */
    public static function addPack(FlatBufferBuilder $builder, $pack)
    {
        $builder->addOffsetX(1, $pack, 0);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param array offset array
     * @return int vector offset
     */
    public static function createPackVector(FlatBufferBuilder $builder, array $data)
    {
        $builder->startVector(4, count($data), 4);
        for ($i = count($data) - 1; $i >= 0; $i--) {
            $builder->putOffset($data[$i]);
        }
        return $builder->endVector();
    }

    /**
     * @param FlatBufferBuilder $builder
     * @param int $numElems
     * @return void
     */
    public static function startPackVector(FlatBufferBuilder $builder, $numElems)
    {
        $builder->startVector(4, $numElems, 4);
    }

    /**
     * @param FlatBufferBuilder $builder
     * @return int table offset
     */
    public static function endFlatBuffersParcel(FlatBufferBuilder $builder)
    {
        $o = $builder->endObject();
        return $o;
    }

    public static function finishFlatBuffersParcelBuffer(FlatBufferBuilder $builder, $offset)
    {
        $builder->finish($offset);
    }
}
