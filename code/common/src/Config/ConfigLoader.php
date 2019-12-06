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
     * @var array
     * 設定yamlファイルの内容
     */
    private $configMap = [];

    /**
     * ConfigLoader コンストラクタ
     * @param string $configFile
     * @param string|null $secondaryConfigFile
     */
    public function __construct(string $configFile, ?string $secondaryConfigFile = null)
    {
        $this->configMap = array_reduce(
            is_null($secondaryConfigFile) ? [$configFile] : [$configFile, $secondaryConfigFile],
            function ($carry, $file) {
                return $this->mergeArray($carry, yaml_parse_file($file));
            },
            []
        );
    }

    /**
     * 配列の内容を後から上書きする関数
     * @param array $accumulator
     * @param array $value
     * @return array
     */
    private function mergeArray(array $accumulator, array $value): array
    {
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                $accumulator[$k] = $this->mergeArray($accumulator[$k] ?? [], $v);
            } else {
                $accumulator[$k] = $v;
            }
        }
        return $accumulator;
    }

    /**
     * config yamlファイルから、指定されたカテゴリー、キーの値を取得する
     * @param string $category
     * @param string $key
     * @param string|null $default
     * @return string
     * @throws ConfigException
     */
    public function getConfig(string $category, string $key, ?string $default): string
    {
        // YAMLに無い値ならばデフォルト値、デフォルト値指定が無ければエラー
        if (!isset($this->configMap[$category]) || !isset($this->configMap[$category][$key])) {
            if (is_string($default)) {
                return $default;
            }
            throw new ConfigException("No such category or key in YAML (Category:${category}, Key:${key})");
        }

        return $this->configMap[$category][$key];
    }
}
