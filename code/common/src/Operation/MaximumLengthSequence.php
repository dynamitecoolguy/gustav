<?php


namespace Gustav\Common\Operation;

/**
 * M系列
 * Class MaximumLengthSequence
 * @package Gustav\Common\Operation
 */
class MaximumLengthSequence
{
    // (P, Q)
    // (2, 1), (3, 1), (4, 1), (5, 2), (6, 1), (7, 1), (7, 3), (10, 3), (15, 4)
    // (17, 3), (18, 7), (21, 2), (22, 1), (25, 3), (28, 3), (29, 2), (33, 13), (35 ,2)
    // (39, 4), (39, 8), (39, 14), (41, 3), (41, 20), (47, 5), (47, 14), (47, 20)
    // (49, 9), (49, 12), (49, 15), (49, 22), (52, 19), (55, 24), (57, 7), (57, 22), (58, 19), (60, 59)

    /**
     * 初期値
     * @var array
     */
    private static $initValue = [
        0, 0, 0, 1, 0, 0, 1, 0, 0, 0,
        1, 0, 0, 1, 0, 0, 1, 0, 0, 1,
        1, 0, 0, 0, 0, 0, 0, 0, 0, 1,
        0, 1, 1, 1, 1, 1, 1, 1, 1
    ];

    /**
     * P値
     * @var int
     */
    private static $p = 39;

    /**
     * Q値
     * @var int
     */
    private static $q = 8;

    /**
     * @var array binary配列
     */
    private $sequence;

    /**
     * @var int M系列計算中のp
     */
    private $pPos;

    /**
     * @var int M系列計算中のq
     */
    private $qPos;

    /**
     * @var int
     */
    private $index;

    /**
     * 初期値は、長さPで、0または1からなる配列でなければならない
     * @param int $p
     * @param int $q
     * @param int $initValue
     */
    public static function setParameter(int $p, int $q, int $initValue): void
    {
        self::$p = $p;
        self::$q = $q;
        self::$initValue = substr(str_repeat('0', self::$p) . decbin($initValue), -self::$p);
    }

    /**
     * MaximumLengthSequence constructor.
     * @param int $index
     * @param int $presetIndex
     * @param int $presetValue
     */
    public function __construct(int $index, int $presetIndex = -1, int $presetValue = -1)
    {
        if ($presetIndex === -1 || $presetValue === -1) {
            $this->pPos = 0;
            $this->qPos = self::$p - self::$q;
            $this->sequence = self::$initValue;
            $this->index = 0;
            $rotateCount = $index;
        } else {
            $this->pPos = $presetIndex % self::$p;
            $this->qPos = ($presetIndex + self::$p - self::$q) % self::$p;
            $preValueBinary = substr(str_repeat('0', self::$p) . decbin($presetValue), -self::$p);
            $preSequence = substr($preValueBinary, self::$p - $this->pPos)
                . substr($preValueBinary, 0, self::$p - $this->pPos);
            $this->sequence = str_split($preSequence);
            $this->index = $presetIndex;
            $rotateCount = $index - $presetIndex;
        }

        for ($i = 0; $i < $rotateCount; $i++) {
            $this->rotate();
        }
    }

    /**
     * 今の値を取得する
     * @return int
     */
    public function getValue(): int
    {
        $array = array_fill(0, self::$p, '0');

        $pos = $this->pPos;
        for ($i = 0; $i < self::$p; $i++) {
            $array[$i] = $this->sequence[$pos];
            $pos = ($pos < self::$p - 1) ? $pos + 1 : 0;
        }

        $width = (int)((self::$p + 3) / 4);
        $digits = substr('000' . implode($array), -$width * 4);
        $result = '';

        for ($i = 0; $i < $width; $i++) {
            $result .= dechex(bindec(substr($digits, $i * 4, 4)));
        }

        return hexdec($result);
    }

    /**
     * インデックスを一つ進める
     */
    public function rotate(): void
    {
        $nextValue = intval($this->sequence[$this->pPos]) ^ intval($this->sequence[$this->qPos]);
        $this->sequence[$this->pPos] = $nextValue;
        $this->pPos = ($this->pPos < self::$p - 1) ? $this->pPos + 1 : 0;
        $this->qPos = ($this->qPos < self::$p - 1) ? $this->qPos + 1 : 0;
        $this->index++;
    }

    /**
     * 今のインデックスを取得する
     * @return int
     */
    public function index(): int
    {
        return $this->index;
    }
}
