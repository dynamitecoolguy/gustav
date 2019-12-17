<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\App\Model\IdentificationModel;
use Gustav\App\Operation\OpenIdConverter;
use Gustav\App\Operation\OpenIdConverterInterface;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLMasterInterface;
use Gustav\Common\Adapter\RedisInterface;
use Gustav\Common\Exception\GustavException;
use Gustav\Common\Operation\KeyOperatorInterface;

/**
 * ユーザ登録処理
 * Class UserRegistration
 * @package Gustav\App\Logic
 */
class UserRegistration
{
    /**
     * @param IdentificationModel $request
     * @param MySQLMasterInterface $mysql
     * @param KeyOperatorInterface $keyOperator
     * @param OpenIdConverterInterface $openIdConverter
     * @param RedisInterface $redis
     * @return IdentificationModel
     * @throws GustavException
     */
    public function __invoke(
        IdentificationModel $request,
        MySQLMasterInterface $mysql,
        KeyOperatorInterface $keyOperator,
        OpenIdConverterInterface $openIdConverter,
        RedisInterface $redis): IdentificationModel
    {
        $note = $request->getNote();

        list($privateKey, $publicKey) = $keyOperator->createKeys();

        $adapter = ($mysql instanceof MySQLAdapter) ? $mysql : new MySQLAdapter($mysql->getPDO(), true);

        list($userId, $openId) = $adapter->executeWithTransaction(
            function (MySQLAdapter $adapter) use ($note, $openIdConverter, $redis, $privateKey, $publicKey) {
                $adapter->execute(
                    'insert into identification(open_id, note) values(0, :note)',
                    [':note' => $note]
                );
                $userId = (int)$adapter->lastInsertId();

                $openId = $openIdConverter->userIdToOpenId($redis, $userId);

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