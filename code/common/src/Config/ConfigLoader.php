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
     * @var bool 設定YAMLファイルを読み込んだかどうか
     */
    private $configLoaded = false;

    /**
     * @var string  設定yamlファイル
     */
    private $configFile;

    /**
     * @var ?string configFile内のssm.class
     */
    private $ssmObjectClass = null;

    /**
     * @var ?array configFile内のssm.parameters
     */
    private $ssmObjectParameters = null;

    /**
     * @var array
     * 設定yamlファイルの内容
     */
    private $configMap = [];

    /**
     * @var array
     * SSMから取得した値
     */
    private $replacedVariableMap = [];

    /**
     * ApplicationSetting constructor.
     * @param string $configFile
     */
    public function __construct(string $configFile)
    {
        $this->configFile = $configFile;
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

        $this->checkLoaded();

        if (is_null($this->ssmObjectClass)) {
            throw new ConfigException("SSM Object class is not defined");
        }

        /** @var SsmObjectInterface $ssmObject */
        $ssmObject = new $this->ssmObjectClass();
        $ssmObject->setUp($this->ssmObjectParameters ?? []);
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
                if (is_string($value) && preg_match('/\$\$(.*)\$\$/', $value, $matches)) { // $$KEY_NAME$$?
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
        if ($this->configLoaded) {
            return;
        }

        // YAMLを読み込んでいなければ読み込む
        $this->configMap = yaml_parse_file($this->configFile);

        $this->ssmObjectClass = $this->configMap['ssm']['class'] ?? null;
        if (!is_null($this->ssmObjectClass)) {
            $this->ssmObjectParameters = $this->configMap['ssm']['parameters'] ?? null;
        }

        $this->configLoaded = true;
    }
}
