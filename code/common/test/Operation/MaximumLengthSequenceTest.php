<?php


namespace Gustav\Common\Operation;


use PHPUnit\Framework\TestCase;

class MaximumLengthSequenceTest extends TestCase
{
    /**
     * @test
     */
    public function pq21()
    {
        MaximumLengthSequence::setParameter(2, 1, 1); // 1 -> 3 -> 2 -> 1...
        $seq = new MaximumLengthSequence(0);
        $this->assertEquals(1, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(3, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(2, $seq->getValue());
        $seq->rotate();
        $this->assertEquals(1, $seq->getValue());
        $this->assertEquals(3, $seq->index());

        MaximumLengthSequence::setParameter(2, 1, 2); // 2 -> 1 -> 3 -> 2 ...
        $seq2 = new MaximumLengthSequence(0);
        $this->assertEquals(2, $seq2->getValue());
        $seq2->rotate();
        $this->assertEquals(1, $seq2->getValue());
        $seq2->rotate();
        $this->assertEquals(3, $seq2->getValue());

        MaximumLengthSequence::setParameter(2, 1, 3); // 3 -> 2 -> 1 -> 3 -> 2
        $seq3 = new MaximumLengthSequence(4);
        $this->assertEquals(2, $seq3->getValue());

        $seq4 = new MaximumLengthSequence(2, 1, 2);
        $this->assertEquals(1, $seq4->getValue());
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
        for ($i = 0; $i < (2 ** 4); $i++) {
            $value = $seq->getValue();
            $index = $seq->index();
            if (isset($hashed[$value])) {
                break;
            }
            $hashed[$value] = $index;
            $seq->rotate();
        }
        $this->assertEquals(15, $index); // 0..15で一周
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
}
