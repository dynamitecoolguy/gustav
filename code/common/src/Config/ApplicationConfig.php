<?php

namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;

/**
 * Class BaseApplicationConfig
 * @package Gustav\Common
 */
class ApplicationConfig implements ApplicationConfigInterface
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
     * @var ConfigLoader
     */
    private $configLoader;

    /**
     * @var array
     * 取得済みの値
     */
    private $fetchedValue = [];

    /**
     * @param ConfigLoader $configLoader
     * ApplicationSetting constructor.
     */
    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * 指定されたカテゴリ内のキーの値を取得する
     * @param string $category
     * @param string $key
     * @param string|null $default
     * @return string
     * @throws ConfigException
     * @implements ApplicationConfigInterface
     */
    public function getValue(string $category, string $key, ?string $default = null): string
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
        $value = $this->configLoader->getConfig($category, $key, $default);

        // SSMによる置換を行う必要がある値か?
        if (strpos($value, '$$') !== false
            && preg_match('/^(.*)\$\$(.*)\$\$(.*)$/', $value, $matches) // $$KEY_NAME$$?
        ) {
            // 置換する
            $replaced = $this->configLoader->replaceVariable($matches[2]);
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
