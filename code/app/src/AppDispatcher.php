<?php


namespace Gustav\App;

use Gustav\App\Logic\TransferOperation;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\BaseDispatcher;
use Gustav\App\Logic\UserRegistration;
use Gustav\App\Model\IdentificationModel;

/**
 * リクエストの処理を割り振る場合、BaseDispatcherの処理を上書きする方法がある.
 * 直接BaseDispatcherを書き換えるよりは、このクラスのように継承したクラスを作成し、AppContainerBuilderのようにDIコンテナを変更する.
 * Class AppDispatcher
 * @package Gustav\App
 */
class AppDispatcher extends BaseDispatcher
{
    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [
            ['REG', IdentificationModel::class, UserRegistration::class],
            ['TRC', TransferCodeModel::class, TransferOperation::class]
        ];
    }
}
