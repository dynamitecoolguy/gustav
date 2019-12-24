<?php


namespace Gustav\App;

use Gustav\App\Logic\AuthenticationLogic;
use Gustav\App\Logic\TransferLogic;
use Gustav\App\Logic\RegistrationLogic;
use Gustav\App\Model\AuthenticationModel;
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
     * @uses \Gustav\App\Logic\RegistrationLogic::register()
     * @uses \Gustav\App\Logic\AuthenticationLogic::request()
     * @uses \Gustav\App\Logic\AuthenticationLogic::publish()
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
                        // [PackType, モデルクラス, 操作callable, トークンのチェックが必要か?(default:true)]
                        return [
                            // ユーザ新規登録
                            ['REG', IdentificationModel::class, [RegistrationLogic::class, 'register'], false],
                            // ユーザ認証
                            ['AUR', AuthenticationModel::class, [AuthenticationLogic::class, 'request'], false],
                            ['AUP', AuthenticationModel::class, [AuthenticationLogic::class, 'publish'], false],

                            ['TRC', TransferCodeModel::class, TransferLogic::class]
                        ];
                    }
                }
            ]
        );
    }
}
