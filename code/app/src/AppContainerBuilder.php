<?php


namespace Gustav\App;


use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfig;

/**
 * Class MgrContainerBuilder
 * @package Gustav\App
 */
class AppContainerBuilder extends BaseContainerBuilder
{
    /**
     * common側の設定に、app用の設定を追加
     * @param ApplicationConfig $config
     * @return array
     */
    protected function getDefinitions(ApplicationConfig $config): array
    {
        return parent::getDefinitions($config) +
            [
                DispatcherInterface::class => new Dispatcher()
            ];
    }
}
