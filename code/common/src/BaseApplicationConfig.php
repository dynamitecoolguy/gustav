<?php

namespace Gustav\Common;

use Gustav\Common\Exception\ConfigException;

/**
 * Class BaseApplicationConfig
 * @package Gustav\Common
 */
class BaseApplicationConfig
{
    /**
     * localにcacheするparameterのTTL
     */
    const PARAMETER_TTL = 60;

    /**
     * Config値をapcuに格納するときのPrefix
     */
    const CONFIG_APCU_PREFIX = 'c_';

    /**
     * @var BaseApplicationConfig
     */
    private static $theInstance = null;

    /**
     * @var ConfigFileLoader
     */
    private $configFileLoader = null;

    /**
     * @var SsmLoader
     */
    private $ssmLoader = null;

    /**
     * @var array
     * 取得済みの値
     */
    private $fetchedValue = [];

    /**
     * @param ConfigFileLoader $configFileLoader
     * @param SsmLoader $ssmLoader
     * @return BaseApplicationConfig インスタンスを取得
     */
    public static function getInstance(ConfigFileLoader $configFileLoader, SsmLoader $ssmLoader): BaseApplicationConfig
    {
        self::$theInstance = self::$theInstance ?? new static($configFileLoader, $ssmLoader);

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
     * @param ConfigFileLoader $configFileLoader
     * @param SsmLoader $ssmLoader
     * ApplicationSetting constructor.
     */
    protected function __construct(ConfigFileLoader $configFileLoader, SsmLoader $ssmLoader)
    {
        $this->configFileLoader = $configFileLoader;
        $this->ssmLoader = $ssmLoader;
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
        $fetchedValue = apcu_fetch($categoryKey);
        if (is_string($fetchedValue)) {
            $this->fetchedValue[$categoryKey] = $fetchedValue;
            return $fetchedValue;
        }

        // config fileから値を取得する
        $value = $this->configFileLoader->getValue($categoryKey, $key);

        // SSMによる置換を行う必要がある値か?
        if (strpos($value, '$$') !== false
            && preg_match('/^(.*)\$\$(.*)\$\$(.*)$/', $value, $matches) // $$KEY_NAME$$?
        ) {
            // 置換する
            $replaced = $this->ssmLoader->replaceValue($this->configFileLoader, $matches[2]);
            $result = $matches[1] . $replaced . $matches[3];
        } else {
            $result = $value;
        }

        // キャッシュする
        if (!apcu_exists($categoryKey)) {
            apcu_store($categoryKey, $result, self::PARAMETER_TTL);
        }
        $this->fetchedValue[$categoryKey] = $result;

        return $result;
    }
}
