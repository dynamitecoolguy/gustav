<?php


namespace Gustav\App;

use Gustav\App\Operation\OpenIdConverter;
use Gustav\App\Operation\OpenIdConverterInterface;
use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\DispatcherInterface;
use function DI\create;

/**
 * BaseContainerBuilderのDIコンテナの定義を変更する場合は、このクラスのようにdefinitionsメソッドを変更する方法がある
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
                DispatcherInterface::class => create(AppDispatcher::class),
                OpenIdConverterInterface::class => create(OpenIdConverter::class)
            ]
        );
    }
}
