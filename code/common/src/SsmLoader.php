<?php


namespace Gustav\Common;

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
class SsmLoader
{
    /**
     * localにcacheするparameterのTTL
     */
    const PARAMETER_TTL = 60;

    /**
     * SSM接続デフォルトリージョン
     */
    const SSM_DEFAULT_REGION = 'ap-northeast-1';
    /**
     * SSM接続デフォルトリージョン
     */
    const SSM_DEFAULT_PROFILE = 'default';

    /**
     * SSMで置換する値をapcuに格納するときのPrefix
     */
    const REPLACED_APCU_PREFIX = 'r_';

    /**
     * @var SsmLoader
     */
    private static $theInstance = null;

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
     * @var array
     * SSMから取得した値
     */
    private $replacedText = [];

    /**
     * @var SsmClient
     */
    private $ssmClient = null;

    /**
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     * @return SsmLoader インスタンスを取得
     */
    public static function getInstance(
        string $accountFile,
        string $region = self::SSM_DEFAULT_REGION,
        string $profile = self::SSM_DEFAULT_PROFILE): SsmLoader
    {
        self::$theInstance = self::$theInstance ?? new static($accountFile, $region, $profile);
        return self::$theInstance;
    }

    /**
     * シングルトンのクリア
     */
    public static function resetInstance(): void
    {
        self::$theInstance = null;
    }

    /**
     * ApplicationSetting constructor.
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     */
    protected function __construct(string $accountFile, string $region, string $profile)
    {
        $this->accountFile = $accountFile;
        $this->region = $region;
        $this->profile = $profile;
    }

    /**
     * 値をSSMから取得した値と置換する
     * @param ConfigFileLoader $configFileLoader
     * @param string $original
     * @return string
     * @throws ConfigException
     */
    public function replaceValue(ConfigFileLoader $configFileLoader, string $original)
    {
        // 登録済の値か?
        if (isset($this->replacedText[$original])) {
            return $this->replacedText[$original];
        }

        // apcuに保存されている値か?
        $fetchedValue = apcu_fetch(self::REPLACED_APCU_PREFIX . $original);
        if (is_string($fetchedValue)) {
            $this->replacedText[$original] = $fetchedValue;
            return $fetchedValue;
        }

        if (is_null($this->ssmClient)) {
            $this->loadSsmClient($configFileLoader->getAllVariables());
        }

        if (!isset($this->replacedText[$original])) {
            throw new ConfigException("Can't obtain text from SSM(Value:${original})");
        }
        return $this->replacedText[$original];
    }

    /**
     * @param string[] $keys
     * @throws ConfigException
     */
    private function loadSsmClient(array $keys): void
    {
        // SSMサービスへの認証
        $provider = CredentialProvider::ini($this->profile, $this->accountFile);
        $memoizedProvider = CredentialProvider::memoize($provider);

        // SSM Clientの取得
        $ip = NameResolver::getIp('ssm.' . $this->region . '.amazonaws.com');
        $sdk = new Sdk([
            'endpoint' => 'https://' . $ip,
            'region' => $this->region,
            'version' => '2014-11-06',
            'credentials' => $memoizedProvider
        ]);
        $ssm = $sdk->createSsm();

        // SystemManagerのパラメータストアから値を一斉に取得
        try {
            $result = $ssm->getParameters([
                'Names' => $keys
            ]);
        } catch (AwsException $e) {
            throw new ConfigException('Access error to aws(' . $e->getAwsErrorMessage() . ')');
        }

        // APCUにすべてのデータを登録
        foreach ($result['Parameters'] as $parameter) {
            $name = $parameter['Name'];
            $value = $parameter['Value'];
            $key = self::REPLACED_APCU_PREFIX . $name;
            if (!apcu_exists($key)) {
                apcu_store($key, $value, self::PARAMETER_TTL);
            }
            $this->replacedText[$name] = $value;
        }
    }
}
