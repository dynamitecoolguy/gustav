<?php


namespace Gustav\Common\Operation;


use Gustav\Common\Exception\OperationException;
use PHPUnit\Framework\TestCase;

class MaximumLengthSequenceTest extends TestCase
{
    /**
     * @test
     */
    public function pq21()
    {
        MaximumLengthSequence::setParameter(2, 1, 1); // 1 -> 2 -> 3 -> 1...
        $seq = new MaximumLengthSequence(0);
        $this->assertEquals(1, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(2, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(3, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(1, $seq->getValue());
        $this->assertEquals(3, $seq->index());

        MaximumLengthSequence::setParameter(2, 1, 2); // 2 -> 3 -> 3 -> 1 ...
        $seq2 = new MaximumLengthSequence(0);
        $this->assertEquals(2, $seq2->getValue());
        $seq2->rotate();
        $this->assertEquals(3, $seq2->getValue());
        $seq2->rotate();
        $this->assertEquals(1, $seq2->getValue());

        MaximumLengthSequence::setParameter(2, 1, 3); // 3 -> 1 -> 2 -> 3 -> 1
        $seq3 = new MaximumLengthSequence(4);
        $this->assertEquals(1, $seq3->getValue());

        $seq4 = new MaximumLengthSequence(2, 1, 1);
        $this->assertEquals(2, $seq4->getValue());
    }

    /**
     * @test
     */
    public function pq41()
    {
        MaximumLengthSequence::setParameter(4, 1, 12);
        $seq = new MaximumLengthSequence(1);
        $index = 0;
        $hashed[12] = 0;
        $values[0] = 12;
        for ($i = 0; $i < (2 ** 4); $i++) {
            $value = $seq->getValue();
            $index = $seq->index();
            if (isset($hashed[$value])) {
                break;
            }
            $values[$index] = (int)$value;
            $hashed[$value] = $index;
            $seq->rotate();
        }
        $this->assertEquals(15, $index); // 0..15で一周

        for ($i = 0; $i < 15; $i++) {
            $seq = new MaximumLengthSequence($i, $i, $values[$i]);
            $this->assertEquals($values[$i], (int)$seq->getValue());
        }
    }

    /**
     * 時間がかかるので省略
     */
    public function pq187()
    {
        MaximumLengthSequence::setParameter(18, 7, 543210);
        $seq = new MaximumLengthSequence(0);
        $max = (2 ** 18) - 1;
        $index = 0;
        for ($i = 0; $i < $max + 1; $i++) {
            $value = $seq->getValue();
            if (isset($hashed[$value])) {
                $index = $seq->index();
                break;
            }
            $hashed[$value] = $index;
            $seq->rotate();
        }
        $this->assertEquals($max, $index); // 2**18-1で一周
    }

    /**
     * @test
     * @throws OperationException
     */
    public function unset()
    {
        $this->expectException(OperationException::class);

        MaximumLengthSequence::resetParameter();
        new MaximumLengthSequence(0);
    }
}
