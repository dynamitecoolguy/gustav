<?php


namespace Gustav\Common;


use Gustav\Common\Exception\ConfigException;

/**
 * config yamlファイルからパラメータと値を取得する
 * Class ConfigFileLoader
 * @package Gustav\Common
 */
class ConfigFileLoader
{
    /**
     * @var ConfigFileLoader
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
     * @param string $configFile
     * @return ConfigFileLoader インスタンスを取得
     */
    public static function getInstance(string $configFile): ConfigFileLoader
    {
        self::$theInstance = self::$theInstance ?? new static($configFile);
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
     * @param string $configFile
     */
    protected function __construct(string $configFile)
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
    public function getValue(string $category, string $key): string
    {
        $this->checkLoaded();

        // YAMLに無い値ならばエラー
        if (!isset($this->configMap[$category]) || !isset($this->configMap[$category][$key])) {
            throw new ConfigException("No such category or key in YAML (Category:${category}, Key:${key}");
        }

        return $this->configMap[$category][$key];
    }

    /**
     * 全部の値を返す
     * @return string[]
     */
    public function getAllVariables(): array
    {
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
     * YAMLファイルを読み込んでいるかどうか
     */
    private function checkLoaded(): void
    {
        // YAMLを読み込んでいなければ読み込む
        $this->configMap = $this->configMap ??
            (is_string($this->configFile) ? yaml_parse_file($this->configFile) : []);

    }
}
