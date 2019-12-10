<?php


namespace Gustav\App;


use DI\Container;
use Gustav\App\Operation\RedisKeys;
use Gustav\Common\Adapter\RedisAdapter;
use Gustav\Common\Adapter\RedisInterface;
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
        return array_merge(
            parent::getDefinitions($config),
            [
                // Dispatcherをapp側のものに上書き
                DispatcherInterface::class => function (): DispatcherInterface {
                    return new AppDispatcher();
                }
            ]
        );
    }
}
