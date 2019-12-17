<?php


namespace Gustav\Common\Model;

/**
 * Packのリストと、アクセス用トークン
 * Class Parcel
 * @package Gustav\Common\Model
 */
class Parcel
{
    /** @var string  アクセストークン */
    private $token;

    /** @var Pack[] */
    private $packList;

    /**
     * Parcel constructor.
     * @param string $token
     * @param array $packList
     */
    public function __construct(string $token, array $packList)
    {
        $this->token = $token;
        $this->packList = $packList;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return Pack[]
     */
    public function getPackList(): array
    {
        return $this->packList;
    }
}