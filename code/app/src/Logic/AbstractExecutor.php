<?php


namespace Gustav\App\Logic;

use DI\Container;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLInterface;
use Gustav\Common\Adapter\MySQLMasterInterface;
use Gustav\Common\Exception\DatabaseException;
use Gustav\Common\Logic\ExecutorInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * ExecutorInterfaceを実装した仮想基底クラス。実際の処理を行うExecutorクラス共通で使用する処理を実装している.
 * Class AbstractExecutor
 * @package Gustav\App\Logic
 */
abstract class AbstractExecutor implements ExecutorInterface
{
    /**
     * @inheritDoc
     */
    public static function getInstance(): ExecutorInterface
    {
        return new static();
    }

    /**
     * MySQL(マスターDB)用接続オブジェクトの取得
     * @param Container $container
     * @return MySQLAdapter
     * @throws DatabaseException
     */
    public function getMySQLMasterAdapter(Container $container): MySQLAdapter
    {
        try {
            $mysql = $container->get(MySQLMasterInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new DatabaseException('MySQLMasterInterface has no container');
        }
        if (!($mysql instanceof MySQLAdapter)) {
            throw new DatabaseException('MySQLMasterInterface container is not instance of MySQLAdapter');
        }
        return $mysql;
    }

    /**
     * MySQL(スレーブDB)用接続オブジェクトの取得
     * @param Container $container
     * @return MySQLAdapter
     * @throws DatabaseException
     */
    public function getMySQLSlaveAdapter(Container $container): MySQLAdapter
    {
        try {
            $mysql = $container->get(MySQLInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new DatabaseException('MySQLInterface has no container');
        }
        if (!($mysql instanceof MySQLAdapter)) {
            throw new DatabaseException('MySQLInterface container is not instance of MySQLAdapter');
        }
        return $mysql;
    }
}