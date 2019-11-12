<?php

namespace Gustav\Common;

use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\Sdk;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;

/**
 * Class BaseApplicationConfig
 * @package Gustav\Common
 */
class BaseApplicationConfig
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
     * localにcacheするparameterのTTL
     */
    const PARAMETER_TTL = 60;

    /**
     * Config値をapcuに格納するときのPrefix
     */
    const CONFIG_APCU_PREFIX = 'c_';

    /**
     * SSMで置換する値をapcuに格納するときのPrefix
     */
    const REPLACED_APCU_PREFIX = 'r_';

    /**
     * @var BaseApplicationConfig
     */
    private static $self = null;

    /**
     * @var string
     * 設定yamlファイル
     */
    private static $configFile = null;

    /**
     * @var string
     * SSMアクセス用AWSアカウントファイル
     */
    private static $ssmAccountFile = null;

    /**
     * @var string
     * SSMアクセス用AWSアカウントのリージョン
     */
    private static $ssmRegion = null;

    /**
     * @var string
     * SSMアクセス用AWSアカウントのプロファイル
     */
    private static $ssmProfile = null;

    /**
     * @var array
     * 取得済みの値
     */
    private $fetchedValue = [];

    /**
     * @var array
     * SSMから取得した値
     */
    private $replacedText = [];

    /**
     * @var array
     * 設定yamlファイルの内容
     */
    private $configMap = null;

    /**
     * @return BaseApplicationConfig インスタンスを取得
     */
    public static function getInstance(): BaseApplicationConfig
    {
        if (is_null(self::$self)) {
            self::$self = new static();
        }
        return self::$self;
    }

    /**
     * 設定ファイルの指定
     * @param string $configFile
     */
    public static function setConfigFile(string $configFile): void
    {
        self::$configFile = $configFile;
    }

    /**
     * SSMアカウントファイル、リージョン、プロファイル名を指定
     * @param string $accountFile
     * @param string $region
     * @param string $profile
     */
    public static function setSsmFile(
        string $accountFile,
        string $region = self::SSM_DEFAULT_REGION,
        string $profile = self::SSM_DEFAULT_PROFILE): void
    {
        self::$ssmAccountFile = $accountFile;
        self::$ssmRegion = $region;
        self::$ssmProfile = $profile;
    }

    /**
     * ApplicationSetting constructor.
     */
    protected function __construct()
    {
        // do nothing
    }

    /**
     * 指定されたカテゴリ内のキーの値を取得する
     * @param string $category
     * @param string $key
     * @return string
     * @throws ConfigException
     */
    public function getValue(string $category, string $key): string
    {
        $categoryKey = self::CONFIG_APCU_PREFIX . $category . '$$' . $key;

        // すでに取得した値か?
        if (isset($this->fetchedValue[$categoryKey])) {
            return $this->fetchedValue[$categoryKey];
        }

        // apcuに保存されている値か?
        $fetchedValue = apcu_fetch($categoryKey, $success);
        if ($success) {
            $this->fetchedValue[$categoryKey] = $fetchedValue;
            return $fetchedValue;
        }

        // YAMLを読み込んでいなければ読み込む
        if (is_null($this->configMap)) {
            if (is_string(self::$configFile)) {
                $this->configMap = yaml_parse_file(self::$configFile);
            } else {
                $this->configMap = [];
            }
        }

        // YAMLに無い値ならばエラー
        if (!isset($this->configMap[$category]) || !isset($this->configMap[$category][$key])) {
            throw new ConfigException("No such category or key in YAML (Category:${category}, Key:${key}");
        }
        $value = $this->configMap[$category][$key];

        // SSMによる置換を行う必要がある値か?
        if (strpos($value, '$$') !== false
            && preg_match('/^(.*)\$\$(.*)\$\$(.*)$/', $value, $matches) // $$KEY_NAME$$?
        ) {
            $replaced = $this->replaceValue($matches[2]);
            $value = $matches[1] . $replaced . $matches[3];
        }

        // キャッシュする
        if (!apcu_exists($categoryKey)) {
            apcu_store($categoryKey, $value, self::PARAMETER_TTL);
        }
        $this->fetchedValue[$categoryKey] = $value;

        return $value;
    }

    /**
     * @param string $original
     * @return string
     * @throws ConfigException
     */
    private function replaceValue(string $original)
    {
        // このリクエストで置換済みの値か?
        if (isset($this->replacedText[$original])) {
            return $this->replacedText[$original];
        }

        // apcuに保存されている値か?
        $apcuKey = self::REPLACED_APCU_PREFIX . $original;
        $fetchedValue = apcu_fetch($apcuKey, $success);
        if ($success) {
            $this->replacedText[$original] = $fetchedValue;
            return $fetchedValue;
        }

        if (!is_string(self::$ssmAccountFile)) {
            throw new ConfigException('SSM credential file is not specified');
        }

        // SSMサービスへの認証
        $provider = CredentialProvider::ini(self::$ssmProfile, self::$ssmAccountFile);
        $memoizedProvider = CredentialProvider::memoize($provider);

        // SSM Clientの取得
        $ip = NameResolver::getIp('ssm.' . self::$ssmRegion . '.amazonaws.com');
        $sdk = new Sdk([
            'endpoint' => 'https://' . $ip,
            'region' => self::$ssmRegion,
            'version' => '2014-11-06',
            'credentials' => $memoizedProvider
        ]);
        $ssm = $sdk->createSsm();

        // configファイル内のすべての$$キーをリストアップ
        $keys = [];
        foreach ($this->configMap as $map) {
            foreach ($map as $key => $value) {
                if (preg_match('/\$\$(.*)\$\$/', $value, $matches)) { // $$KEY_NAME$$?
                    $keys[] = $matches[1];
                }
            }
        }

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
            if (!apcu_exists($name)) {
                apcu_store($name, $value, self::PARAMETER_TTL);
            }
            $this->replacedText[$name] = $value;
        }

        if (!isset($this->replacedText[$original])) {
            throw new ConfigException("Can't obtain text from SSM(Value:${original})");
        }
        return $this->replacedText[$original];
    }
}
