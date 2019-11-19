<?php


namespace Gustav\Common\Config;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Aws\Ssm\SsmClient;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;

/**
 * SSMからパラメータを読んでくる
 * Class SsmLoader
 * @package Gustav\Common
 */
class SsmObjectMaker
{
    /**
     * SSM接続デフォルトリージョン
     */
    const SSM_DEFAULT_REGION = 'ap-northeast-1';
    /**
     * SSM接続デフォルトリージョン
     */
    const SSM_DEFAULT_PROFILE = 'default';

    /**
     * @var SsmObjectMaker
     */
    private static $theInstance = null;

    /**
     * @var string
     * 内部で使用するSsmObjectクラス
     */
    private $ssmObjectClass = null;

    /**
     * @var string
     * SSMへの接続アカウントが記載されたファイル
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
     * @param string $objectClass
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     * @return SsmObjectMaker インスタンスを取得
     */
    public static function getInstance(
        string $objectClass,
        string $accountFile,
        string $region = self::SSM_DEFAULT_REGION,
        string $profile = self::SSM_DEFAULT_PROFILE): SsmObjectMaker
    {
        self::$theInstance = self::$theInstance ?? new static($objectClass, $accountFile, $region, $profile);
        return self::$theInstance;
    }

    /**
     * シングルトンのクリア
     * For development only.
     */
    public static function resetInstance(): void
    {
        self::$theInstance = null;
    }

    /**
     * Constructor.
     * @param string $objectClass
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     */
    protected function __construct(string $objectClass, string $accountFile, string $region, string $profile)
    {
        $this->ssmObjectClass = $objectClass;
        $this->accountFile = $accountFile;
        $this->region = $region;
        $this->profile = $profile;
    }

    /**
     * @return AbstractSsmObject
     */
    public function getSsmObject(): AbstractSsmObject
    {
        return new $this->ssmObjectClass($this->accountFile, $this->region, $this->profile);
    }
}
