<?php


namespace Gustav\App;

use Gustav\App\Logic\TransferOperation;
use Gustav\App\Logic\UserRegistration;
use Gustav\App\Model\IdentificationModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\App\Operation\OpenIdConverter;
use Gustav\App\Operation\OpenIdConverterInterface;
use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Network\DispatcherTableInterface;
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
     * @uses \Gustav\App\Logic\UserRegistration::register()
     */
    protected function getDefinitions(ApplicationConfigInterface $config): array
    {
        return array_merge(
            parent::getDefinitions($config),
            [
                OpenIdConverterInterface::class => create(OpenIdConverter::class),
                DispatcherTableInterface::class => new class implements DispatcherTableInterface {
                    public function getDispatchTable(): array
                    {
                        return [
                            ['REG', IdentificationModel::class, [UserRegistration::class, 'register']],
                            ['TRC', TransferCodeModel::class, TransferOperation::class]
                        ];
                    }
                }
            ]
        );
    }
}
