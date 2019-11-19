<?php


namespace Gustav\Common\Config;

/**
 * SSMからパラメータを読んでくる
 * Class SsmLoader
 * @package Gustav\Common
 */
abstract class AbstractSsmObject
{
    /**
     * @var string
     * SSMアクセス用AWSアカウントファイル
     */
    private $accountFile = null;

    /**
     * @var string
     * SSMアクセス用AWSアカウントのリージョン
     */
    private $region = null;

    /**
     * @var string
     * SSMアクセス用AWSアカウントのプロファイル
     */
    private $profile = null;

    /**
     * ApplicationSetting constructor.
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     */
    public function __construct(string $accountFile, string $region, string $profile)
    {
        $this->accountFile = $accountFile;
        $this->region = $region;
        $this->profile = $profile;
    }

    /**
     * @param string[] $keys
     * @return string[]
     */
    public abstract function getParameters(array $keys): array;

    /**
     * @return string
     */
    public function getAccountFile(): string
    {
        return $this->accountFile;
    }

    /**
     * @return string
     */
    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @return string
     */
    public function getProfile(): string
    {
        return $this->profile;
    }
}
