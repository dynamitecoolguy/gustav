<?php


namespace Gustav\Common\Config;


use Gustav\Common\Exception\ConfigException;

/**
 * config yamlファイルからパラメータと値を取得する
 * Class ConfigLoader
 * @package Gustav\Common
 */
class ConfigLoader
{
    /**
     * localにcacheするparameterのTTL
     */
    const VARIABLE_TTL = 60;

    /**
     * SSMで置換する値をapcuに格納するときのPrefix
     */
    const REPLACED_APCU_PREFIX = 'r_';

    /**
     * @var ConfigLoader
     */
    private static $theInstance = null;

    /**
     * @var string
     * 設定yamlファイル
     */
    private $configFile = null;

    /**
     * @var array
     * 設定yamlファイルの内容
     */
    private $configMap = null;

    /**
     * @var array
     * SSMから取得した値
     */
    private $replacedVariableMap = [];

    /**
     * @var SsmObjectMaker
     */
    private $ssmObjectMaker = null;

    /**
     * @param string $configFile
     * @param SsmObjectMaker $ssmObjectMaker
     * @return ConfigLoader インスタンスを取得
     */
    public static function getInstance(string $configFile, SsmObjectMaker $ssmObjectMaker): ConfigLoader
    {
        self::$theInstance = self::$theInstance ?? new static($configFile, $ssmObjectMaker);
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
     * ApplicationSetting constructor.
     * @param string $configFile
     * @param SsmObjectMaker $ssmObjectMaker
     */
    protected function __construct(string $configFile, SsmObjectMaker $ssmObjectMaker)
    {
        $this->configFile = $configFile;
        $this->ssmObjectMaker = $ssmObjectMaker;
    }

    /**
     * config yamlファイルから、指定されたカテゴリー、キーの値を取得する
     * @param string $category
     * @param string $key
     * @return string
     * @throws ConfigException
     */
    public function getConfig(string $category, string $key): string
    {
        $this->checkLoaded();

        // YAMLに無い値ならばエラー
        if (!isset($this->configMap[$category]) || !isset($this->configMap[$category][$key])) {
            throw new ConfigException("No such category or key in YAML (Category:${category}, Key:${key}");
        }

        return $this->configMap[$category][$key];
    }

    /**
     * @param string $variable
     * @return string
     * @throws ConfigException
     */
    public function replaceVariable(string $variable): string
    {
        $cached = $this->getCachedVariable($variable);
        if ($cached !== false) {
            return $cached;
        }

        $ssmObject = $this->ssmObjectMaker->getSsmObject();
        $ssmVariables = $ssmObject->getParameters($this->getAllVariables());
        foreach ($ssmVariables as $key => $value) {
            $this->replacedVariableMap[$key] = $value;
            apcu_store(self::REPLACED_APCU_PREFIX . $key, $value, self::VARIABLE_TTL);
        }

        // 登録済の値か?
        if (isset($this->replacedVariableMap[$variable])) {
            return $this->replacedVariableMap[$variable];
        }

        throw new ConfigException("No such variable in SSM (${variable}");
    }

    /**
     * 全部の値を返す
     * @return string[]
     */
    public function getAllVariables(): array
    {
        $this->checkLoaded();

        $keys = [];
        foreach ($this->configMap as $map) {
            foreach ($map as $key => $value) {
                if (preg_match('/\$\$(.*)\$\$/', $value, $matches)) { // $$KEY_NAME$$?
                    $keys[$matches[1]] = 1;
                }
            }
        }

        return array_keys($keys);
    }

    /**
     * 値をキャッシュから取得する。無ければfalse
     * @param string $variable
     * @return string|bool
     */
    private function getCachedVariable(string $variable)
    {
        // 登録済の値か?
        if (isset($this->replacedVariableMap[$variable])) {
            return $this->replacedVariableMap[$variable];
        }

        // apcuに保存されている値か?
        $fetchedValue = apcu_fetch(self::REPLACED_APCU_PREFIX . $variable);
        if (is_string($fetchedValue)) {
            $this->replacedVariableMap[$variable] = $fetchedValue;
            return $fetchedValue;
        }

        return false;
    }

    /**
     * YAMLファイルを読み込んでいるかどうか
     */
    private function checkLoaded(): void
    {
        // YAMLを読み込んでいなければ読み込む
        $this->configMap = $this->configMap ??
            (is_string($this->configFile) ? yaml_parse_file($this->configFile) : []);
    }
}
