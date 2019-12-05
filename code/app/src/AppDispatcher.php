<?php


namespace Gustav\App;

use Gustav\Common\BaseDispatcher;
use Gustav\App\Logic\RegistrationExecutor;
use Gustav\App\Model\RegistrationModel;

class AppDispatcher extends BaseDispatcher
{
    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [
            ['REG', RegistrationModel::class, RegistrationExecutor::class]
        ];
    }
}
