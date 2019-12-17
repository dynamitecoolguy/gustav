<?php


namespace Gustav\App\Logic;

use Gustav\App\Database\IdentificationTable;
use Gustav\App\Database\KeyPairTable;
use Gustav\App\Model\IdentificationModel;
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
                $userId = IdentificationTable::insert($adapter, $note);
                $openId = $openIdConverter->userIdToOpenId($redis, $userId);
                IdentificationTable::updateOpenId($adapter, $userId, $openId);

                KeyPairTable::insert($adapter, $userId, $privateKey, $publicKey);

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