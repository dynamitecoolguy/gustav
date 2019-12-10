<?php


namespace Gustav\App;

use Gustav\Common\BaseDispatcher;
use Gustav\App\Logic\UserRegistration;
use Gustav\App\Model\IdentificationModel;

class AppDispatcher extends BaseDispatcher
{
    /**
     * 必要であればアプリケーション側でoverrideする
     * @return array
     */
    protected static function getModelAndExecutor(): array
    {
        return [
            ['REG', IdentificationModel::class, UserRegistration::class]
        ];
    }
}
