<?php


namespace Gustav\Common\Operation;

use GMP;
use Gustav\Common\Exception\UninitializedException;

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
     * @var ?GMP
     */
    private static $initValue = null;

    /**
     * P値
     * @var int
     */
    private static $p;

    /**
     * Q値
     * @var int
     */
    private static $q;

    /**
     * @var GMP binary配列
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
     * @param mixed $initValue
     */
    public static function setParameter(int $p, int $q, $initValue): void
    {
        self::$p = $p;
        self::$q = $q;
        self::$initValue = gmp_init($initValue, 10);
    }

    /**
     * パラメータの初期化。テスト用
     */
    public static function resetParameter(): void
    {
        self::$initValue = null;
    }

    /**
     * MaximumLengthSequence constructor.
     * @param int $index
     * @param int $presetIndex
     * @param mixed|null $presetValue
     * @throws UninitializedException
     */
    public function __construct(int $index, int $presetIndex = -1, $presetValue = null)
    {
        if (is_null(self::$initValue)) {
            throw new UninitializedException('Must call setParameter before construction');
        }

        if ($presetIndex === -1 || is_null($presetValue) || $presetIndex > $index) {
            $this->pPos = 0;
            $this->qPos = self::$p - self::$q;
            $this->sequence = gmp_init(gmp_strval(self::$initValue));
            $this->index = 0;
            $rotateCount = $index;
        } else {
            $this->pPos = $presetIndex % self::$p;
            $this->qPos = ($presetIndex + self::$p - self::$q) % self::$p;

            // 初期値
            $presetValueGmp = gmp_init($presetValue, 10);

            // 初期値を途中から計算できるように必要なだけビット回転させる
            $presetValueBinary = $this->gmp2bin($presetValueGmp);
            $presetValueSequence = substr($presetValueBinary, $this->pPos)
                . substr($presetValueBinary, 0, $this->pPos);
            $this->sequence = gmp_init($presetValueSequence, 2);
            $this->index = $presetIndex;
            $rotateCount = $index - $presetIndex;
        }

        // 必要なだけ回転する
        for ($i = 0; $i < $rotateCount; $i++) {
            $this->rotate();
        }
    }

    /**
     * 今の値を取得する
     * @return string
     */
    public function getValue(): string
    {
        // 計算用の値をbinaryに変換
        $rotated = $this->gmp2bin($this->sequence);

        // binaryを数値にするため、必要なだけビット回転させる
        $value = substr($rotated, self::$p - $this->pPos) . substr($rotated, 0, self::$p - $this->pPos);

        // ビットを再びgmpに変換
        $gmp = gmp_init($value, 2);

        // 文字列に変換
        return gmp_strval($gmp, 10);
    }

    /**
     * インデックスを一つ進める
     */
    public function rotate(): void
    {
        $pBit = gmp_testbit($this->sequence, $this->pPos);
        $qBit = gmp_testbit($this->sequence, $this->qPos);
        $nextValue = $pBit ^ $qBit;
        gmp_setbit($this->sequence, $this->pPos, $nextValue);

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

    /**
     * GMPをバイナリ文字列に変換。ただし、桁数は常にself::$p
     * @param GMP $gmp
     * @return string
     */
    private function gmp2bin(GMP $gmp): string
    {
        return substr(str_repeat('0', self::$p - 1) . gmp_strval($gmp, 2), -self::$p);
    }
}
