<?php


namespace Gustav\App\Logic;


use DI\Container;
use Gustav\App\Model\RegistrationModel;
use Gustav\Common\Exception\ModelException;
use Gustav\Common\Model\ModelInterface;

class RegistrationExecutor extends AbstractExecutor
{
    /**
     * @param int            $version       // フォーマットバージョン
     * @param Container      $container     // DI\Container
     * @param ModelInterface $request       // リクエストオブジェクト
     * @return ModelInterface|null
     * @throws ModelException
     */
    public function execute(int $version, Container $container, ModelInterface $request): ?ModelInterface
    {
        if (!($request instanceof RegistrationModel)) {
            throw new ModelException('Request object is not expected class');
        }
        $campaignCode = $request->getCampaignCode();
        // TODO: Implement execute() method.
        return null;
    }
}