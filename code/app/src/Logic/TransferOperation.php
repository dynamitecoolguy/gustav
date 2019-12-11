<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\TransferCodeModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;

/**
 * Class TransferOperation
 * @package Gustav\App\Logic
 */
class TransferOperation extends AbstractExecutor
{
    /**
     * @inheritDoc
     */
    public function execute(Container $container, ModelChunk $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();
        if (!($request instanceof TransferCodeModel)) {
            throw new ModelException('Request object is not expected class');
        }

        // パラメータ
        $userId = $request->getUserId();             /** @var int    $userId */
        $transferCode = $request->getTransferCode(); /** @var string $transferCode */
        $password = $request->getPassword();         /** @var string $password */

        // UserIdのみ -> TransferCodeの取得
        // UserId + TransferCode + OldPassword + NewPassword -> Passwordの変更
        // TransferCode + OldPassword -> UserIdの取得
        // UserId + OldPassword -> TransfetCodeの再発行
    }
}