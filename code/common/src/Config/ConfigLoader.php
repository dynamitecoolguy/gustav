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
     * @var string[]
     */
    private $yamlFiles;

    /**
     * @var array|null
     * 設定yamlファイルの内容
     */
    private $configMap = null;

    /**
     * ConfigLoader コンストラクタ
     * @param string $configFile
     * @param string|null $secondaryConfigFile
     */
    public function __construct(string $configFile, ?string $secondaryConfigFile = null)
    {
        $this->yamlFiles = is_null($secondaryConfigFile) ? [$configFile] : [$configFile, $secondaryConfigFile];
    }

    /**
     * yamlファイルを読み込んでいなければ読み込む
     */
    private function checkMap(): void
    {
        if (is_null($this->configMap)) {
            $this->configMap = array_reduce(
                $this->yamlFiles,
                function ($carry, $file) {
                    return $this->mergeArray($carry, yaml_parse_file($file));
                },
                []
            );
        }
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
        // YAML読み込みチェック
        $this->checkMap();

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
