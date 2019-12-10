<?php


namespace Gustav\App\Logic;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Gustav\Common\Adapter\MySQLAdapter;
use Gustav\Common\Adapter\MySQLMasterInterface;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Logic\ExecutorInterface;
use Psr\Container\ContainerExceptionInterface;

abstract class AbstractExecutor implements ExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function getInstance(): ExecutorInterface
    {
        return new static();
    }

    /**
     * @param Container $container
     * @return MySQLAdapter
     * @throws ModelException
     */
    public function getMySQLMasterAdapter(Container $container): MySQLAdapter
    {
        try {
            $mysql = $container->get(MySQLMasterInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new ModelException('MySQLMasterInterface has no container');
        }
        if (!($mysql instanceof MySQLAdapter)) {
            throw new ModelException('MySQLMasterInterface container is not instance of MySQLAdapter');
        }
        return $mysql;
    }

    /**
     * @param Container $container
     * @return MySQLAdapter
     * @throws ModelException
     */
    public function getMySQLSlaveAdapter(Container $container): MySQLAdapter
    {
        try {
            $mysql = $container->get(MySQLInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new ModelException('MySQLInterface has no container');
        }
        if (!($mysql instanceof MySQLAdapter)) {
            throw new ModelException('MySQLInterface container is not instance of MySQLAdapter');
        }
        return $mysql;
    }
}