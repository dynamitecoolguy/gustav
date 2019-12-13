<?php

namespace Gustav\Common\Config;

/**
 * Config取得用クラス. 一度読み込んだ設定内容はローカルとAPCUにキャッシュされる。APCU側のキャッシュはPARAMETER_TTL秒の間保持される.
 * Class ApplicationConfig
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
     * @var array 取得済みの値
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
     * @inheritDoc
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
        $result = $this->configLoader->getConfig($category, $key, $default);

        // キャッシュする
        if (!apcu_exists($categoryKey)) {
            apcu_store($categoryKey, $result, self::PARAMETER_TTL);
        }
        $this->fetchedValue[$categoryKey] = $result;

        return $result;
    }
}
