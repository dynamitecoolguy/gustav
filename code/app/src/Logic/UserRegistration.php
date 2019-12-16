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
use Gustav\Common\Operation\KeyOperatorInterface;

/**
 * ユーザ登録処理
 * Class UserRegistration
 * @package Gustav\App\Logic
 */
class UserRegistration extends AbstractExecutor
{
    /**
     * @inheritDoc
     */
    public function execute(Container $container, ModelChunk $requestObject): ?ModelInterface
    {
        $request = $requestObject->getModel();
        if (!($request instanceof IdentificationModel)) {
            throw new ModelException('Request object is not expected class');
        }

        $note = $request->getNote();

        $keyOperator = $container->get(KeyOperatorInterface::class);
        list($privateKey, $publicKey) = $keyOperator->createKeys();

        $mysql = $this->getMySQLMasterAdapter($container);

        list($userId, $openId) = $mysql->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($note, $container, $privateKey, $publicKey) {
                $adapter->execute(
                    'insert into identification(open_id, note) values(0, :note)',
                    [':note' => $note]
                );
                $userId = (int)$adapter->lastInsertId();

                $openId = OpenIdConverter::userIdToOpenId($container, $userId);

                $adapter->execute(
                    'update identification set open_id=:oid where user_id=:uid',
                    [':oid' => (int)$openId, ':uid' => $userId]
                );

                $adapter->execute(
                    'insert into key_pair(user_id, private_key, public_key) values(:uid, :pri, :pub)',
                    [':uid' => $userId, ':pri' => $privateKey, ':pub' => $publicKey]
                );

                return [$userId, $openId];
            }
        );

        return new IdentificationModel([
            IdentificationModel::USER_ID => $userId,
            IdentificationModel::OPEN_ID => $openId,
            IdentificationModel::NOTE => $note,
            IdentificationModel::PRIVATE_KEY => $privateKey,
            IdentificationModel::PUBLIC_KEY => $publicKey
        ]);
    }
}