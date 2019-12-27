<?php


namespace Gustav\App;

use Gustav\App\Logic\AuthenticationLogic;
use Gustav\App\Logic\TransferLogic;
use Gustav\App\Logic\RegistrationLogic;
use Gustav\App\Model\AuthenticationModel;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\BaseContainerBuilder;
use Gustav\Common\Network\DispatcherTableInterface;

/**
 * BaseContainerBuilderのDIコンテナの定義を変更する場合は、このクラスのようにdefinitionsメソッドを変更する方法がある
 * Class AppContainerBuilder
 * @package Gustav\App
 */
class AppContainerBuilder extends BaseContainerBuilder
{
    /**
     * @return DispatcherTableInterface|null
     */
    protected function getDispatcherTable(): ?DispatcherTableInterface
    {
        return new class implements DispatcherTableInterface {
            public function getDispatchTable(): array
            {
                // [PackType, モデルクラス, 操作callable, トークンのチェックが必要か?(default:true)]
                return [
                    // ユーザ新規登録
                    [
                        RegistrationLogic::REGISTER_ACTION,
                        RegistrationModel::class,
                        [RegistrationLogic::class, 'register'],
                        false
                    ],
                    // ユーザ認証
                    [
                        AuthenticationLogic::REQUEST_ACTION,
                        AuthenticationModel::class,
                        [AuthenticationLogic::class, 'request'],
                        false
                    ],
                    [
                        AuthenticationLogic::PUBLISH_ACTION,
                        AuthenticationModel::class,
                        [AuthenticationLogic::class, 'publish'],
                        false
                    ],
                    // ユーザ移管
                    [
                        TransferLogic::SET_PASSWORD_ACTION,
                        TransferCodeModel::class,
                        [TransferLogic::class, 'setPassword']
                    ],
                    [
                        TransferLogic::EXECUTE_ACTION,
                        TransferCodeModel::class,
                        [TransferLogic::class, 'execute'],
                        false
                    ],
                ];
            }
        };
    }
}
