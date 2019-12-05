<?php


namespace Gustav\App;


use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\DispatcherInterface;

/**
 * Class AppContainerBuilder
 * @package Gustav\App
 */
class AppContainerBuilder extends BaseContainerBuilder
{
    /**
     * common側の設定に、app用の設定を追加
     * @param ApplicationConfigInterface $config
     * @return array
     */
    protected function getDefinitions(ApplicationConfigInterface $config): array
    {
        $definitions = parent::getDefinitions($config);
        $definitions[DispatcherInterface::class] = function (): DispatcherInterface {
            return new AppDispatcher();
        };
        return $definitions;
    }
}
