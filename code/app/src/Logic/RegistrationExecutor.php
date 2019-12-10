<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\RegistrationModel;
use Gustav\App\Operation\OpenIdConverter;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelInterface;

/**
 * Class RegistrationExecutor
 * @package Gustav\App\Logic
 */
class RegistrationExecutor extends AbstractExecutor
{
    /**
     * @param int $version // フォーマットバージョン
     * @param Container $container // DI\Container
     * @param ModelInterface $request // リクエストオブジェクト
     * @return ModelInterface|null
     * @throws ModelException
     * @throws DatabaseException
     */
    public function execute(int $version, Container $container, ModelInterface $request): ?ModelInterface
    {
        if (!($request instanceof RegistrationModel)) {
            throw new ModelException('Request object is not expected class');
        }

        $campaignCode = $request->getCampaignCode();

        $mysql = $this->getMySQLMasterAdapter($container);

        list($userId, $openId) = $mysql->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($campaignCode, $container) {
                $adapter->execute(
                    'insert into registration(open_id, campaign_code) values(0, :code)',
                    [':code' => $campaignCode]
                );
                $userId = (int)$adapter->lastInsertId();

                $openId = OpenIdConverter::userIdToOpenId($container, $userId);

                $adapter->execute(
                    'update registration set open_id=:oid where user_id=:uid',
                    [':oid' => (int)$openId, ':uid' => $userId]
                );
                return [$userId, $openId];
            }
        );

        return new RegistrationModel($userId, $openId, $campaignCode);
    }
}