<?php


namespace Gustav\Common\Adapter;

use Aws\Sdk;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;

/**
 * AWS SDKインスタンスを作成する
 * Class AwsSdkAdapter
 * @package Gustav\Common\Adapter
 */
class AwsSdkFactory
{
    const DEFAULT_AWS_REGION = 'ap-northeast-1';

    /**
     * @param ApplicationConfigInterface $config
     * @param string $category
     * @param string $version
     * @param array|null $additionalParameters
     * @return Sdk
     * @throws ConfigException
     */
    public static function create(
        ApplicationConfigInterface $config,
        string $category,
        string $version,
        ?array $additionalParameters = null
    ) {
        $params = [
            'endpoint' => $config->getValue($category, 'endpoint'),
            'region' => $config->getValue($category, 'region', self::DEFAULT_AWS_REGION),
            'version' => $version,
            'credentials' => [
                'key' => $config->getValue($category, 'key'),
                'secret' => $config->getValue($category, 'secret')
            ]
        ];
        if (!is_null($additionalParameters)) {
            $params += $additionalParameters;
        }
        return new Sdk($params);
    }
}