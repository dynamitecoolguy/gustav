<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Model\ModelInterface;

/**
 * Class TransferOperation
 * @package Gustav\App\Logic
 */
class TransferOperation
{
    public function __invoke(Container $container, TransferCodeModel $request): ?ModelInterface
    {
        // パラメータ
        $userId = $request->getUserId();             /** @var int    $userId */
        $transferCode = $request->getTransferCode(); /** @var string $transferCode */
        $password = $request->getPassword();         /** @var string $password */

        // UserIdのみ -> TransferCodeの取得
        // UserId + TransferCode + OldPassword + NewPassword -> Passwordの変更
        // TransferCode + OldPassword -> UserIdの取得
        // UserId + OldPassword -> TransferCodeの再発行

        // TODO:
    }
}