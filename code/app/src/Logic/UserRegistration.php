<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\IdentificationModel;
use Gustav\App\Operation\OpenIdConverter;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelChunk;
use Gustav\Common\Model\ModelInterface;

/**
 * ユーザ登録処理
 * Class UserRegistration
 * @package Gustav\App\Logic
 */
class UserRegistration extends AbstractExecutor
{
    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function execute(Container $container, ModelChunk $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();
        if (!($request instanceof IdentificationModel)) {
            throw new ModelException('Request object is not expected class');
        }

        $campaignCode = $request->getCampaignCode();

        $mysql = $this->getMySQLMasterAdapter($container);

        list($userId, $openId) = $mysql->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($campaignCode, $container) {
                $adapter->execute(
                    'insert into identification(open_id, campaign_code) values(0, :code)',
                    [':code' => $campaignCode]
                );
                $userId = (int)$adapter->lastInsertId();

                $openId = OpenIdConverter::userIdToOpenId($container, $userId);

                $adapter->execute(
                    'update identification set open_id=:oid where user_id=:uid',
                    [':oid' => (int)$openId, ':uid' => $userId]
                );
                return [$userId, $openId];
            }
        );

        return new IdentificationModel([
            IdentificationModel::USER_ID => $userId,
            IdentificationModel::OPEN_ID => $openId,
            IdentificationModel::CAMPAIGN_CODE => $campaignCode
        ]);
    }
}