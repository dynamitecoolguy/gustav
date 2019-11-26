<?php


namespace Gustav\Common\Log;


use Fluent\Logger\Entity;

class EntityUs extends Entity
{
    /* @var float w/microtime */
    private $microTime;

    /**
     * create a entity for sending to fluentd server
     *
     * @param string $tag
     * @param array $data
     * @param float $microTime
     */
    public function __construct(string $tag, array $data, float $microTime)
    {
        parent::__construct($tag, $data + ['now' => $microTime], intval($microTime));

        $this->microTime = $microTime;
    }

    /**
     * @return float
     */
    public function getMicroTime(): float
    {
        return $this->microTime;
    }
}
