<?php


namespace Gustav\Common\Operation;

use Redis;

/**
 * Class Ranking
 * @package Gustav\Common\Operation
 */
class Ranking
{
    /**
     * @var Redis $redis
     */
    private $redis;

    /**
     * @var string $key
     */
    private $key;

    /**
     * Ranking constructor.
     * @param Redis $redis   Redisオブジェクト
     * @param string $key    ランキングのキー
     */
    public function __construct(Redis $redis, string $key)
    {
        $this->redis = $redis;
        $this->key = $key;
    }

    /**
     * 注意! ランキングのフルリセット
     */
    public function reset(): void
    {
        $this->redis->del($this->key);
    }

    /**
     * スコアのセット
     * Time: O(log(N))
     * @param string $member
     * @param int $score
     */
    public function set(string $member, int $score): void
    {
        $this->redis->zAdd($this->key, [], $score, $member);
    }

    /**
     * スコアの加減算
     * Time: O(log(N))
     * @param string $member
     * @param int $score
     */
    public function incrBy(string $member, int $score): void
    {
        $this->redis->zIncrBy($this->key, $score, $member);
    }

    /**
     * メンバー数を返す
     * Time: O(1)
     * @return int
     */
    public function count(): int
    {
        return $this->redis->zCard($this->key);
    }

    /**
     * メンバーのスコアを返す
     * Time: O(1)
     * @param string $member
     * @return int
     */
    public function score(string $member): int
    {
        $score = $this->redis->zScore($this->key, $member);
        return intval($score);
    }

    /**
     * 指定されたスコアの順位(降順)を返す
     * Time: O(log(N))
     * @param int $score
     * @return int
     */
    public function rankDesc(int $score): int
    {
        return 1 + $this->redis->zCount($this->key, "(${score}", '+Inf');
    }

    /**
     * 指定されたスコアの順位(昇順)を返す
     * Time: O(log(N))
     * @param int $score
     * @return int
     */
    public function rankAsc(int $score): int
    {
        return 1 + $this->redis->zCount($this->key, '-Inf', "(${score}");
    }

    /**
     * 指定された順位から順位のメンバーとスコア一覧を返す(降順)
     * 1位から5位ならば、 from=1, to=5
     * Time: O(log(N) + M) x 3
     * @param int $from
     * @param int $to
     * @return array
     */
    public function rangeDesc(int $from, int $to): array
    {
        $first = $this->redis->zRevRange($this->key, $from - 1, $from - 1, true);
        $last  = $this->redis->zRevRange($this->key, $to - 1, $to - 1, true);
        if (empty($first) && empty($last)) {
            return [];
        }
        $firstVal = empty($first) ? '+Inf' : array_values($first)[0];
        $lastVal = empty($last) ? '-Inf' : array_values($last)[0];

        $members = $this->redis->zRevRangeByScore($this->key, $firstVal, $lastVal, ['withscores' => true]);

        return $this->zRangeByScoreToResult($members, $from, PHP_INT_MAX);
    }

    /**
     * 指定された順位から順位のメンバーとスコア一覧を返す(昇順)
     * 1位から5位ならば、 from=1, to=5
     * Time: O(log(N) + M) x 3
     * @param int $from
     * @param int $to
     * @return array
     */
    public function rangeAsc(int $from, int $to): array
    {
        $first = $this->redis->zRange($this->key, $from - 1, $from - 1,true);
        $last  = $this->redis->zRange($this->key, $to - 1, $to - 1, true);
        if (empty($first) && empty($last)) {
            return [];
        }
        $firstVal = empty($first) ? '-Inf' : array_values($first)[0];
        $lastVal = empty($last) ? '+Inf' : array_values($last)[0];

        $members = $this->redis->zRangeByScore($this->key, $firstVal, $lastVal, ['withscores' => true]);

        return $this->zRangeByScoreToResult($members, $from, PHP_INT_MIN);
    }

    private function zRangeByScoreToResult(array $members, int $from, int $initialValue)
    {
        return array_reduce(
            array_keys($members),
            function (array $carry, string $member) : array
            {
                list($members, $prevScore, $rank, $step, $result) = $carry;
                $score = $members[$member];

                if ($score !== $prevScore) {
                    $prevScore = $score;
                    $rank += $step;
                    $step = 1;
                } else {
                    $step++;
                }
                $result[] = [$rank, $member, $score];

                return [$members, $prevScore, $rank, $step, $result];
            },
            [$members, $initialValue, $from - 1, 1, []]
        )[4];
    }

    /**
     * 指定されたスコアの次のスコアを返す(降順)
     * Time: O(log(N) + M) x 3
     * @param int $score
     * @return int|null
     */
    public function greater(int $score): ?int
    {
        $members = $this->redis->zRangeByScore($this->key, "(${score}", '+Inf',
            ['withscores' => true, 'limit' => [0, 1]]);

        return empty($members) ? null : array_values($members)[0];
    }

    /**
     * 指定されたスコアの次のスコアを返す(降順)
     * Time: O(log(N) + M) x 3
     * @param int $score
     * @return int|null
     */
    public function lesser(int $score): ?int
    {
        $members = $this->redis->zRevRangeByScore($this->key, "(${score}", '-Inf',
            ['withscores' => true, 'limit' => [0, 1]]);

        return empty($members) ? null : array_values($members)[0];
    }
}
