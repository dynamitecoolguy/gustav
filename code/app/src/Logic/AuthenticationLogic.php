<?php


namespace Gustav\App\Logic;


use Gustav\App\Model\AuthenticationModel;

class AuthenticationLogic
{
    public function request(
        AuthenticationModel $request
    ): AuthenticationModel
    {
        // ユーザID
        $userId = $request->getUserId();
    }

    public function publish(): AuthenticationModel
    {

    }
}