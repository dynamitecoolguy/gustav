<?php


namespace Gustav\Common\Log;


use Gustav\Common\Adapter\SqsInterface;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Class DataLoggerFactory
 * @package Gustav\Common\Log
 */
class DataLoggerFactory
{
    /**
     * @param ApplicationConfigInterface $config
     * @param ContainerInterface $container
     * @return DataLoggerFluent|DataLoggerSqs
     * @throws ConfigException
     */
    public static function create(ApplicationConfigInterface $config, ContainerInterface $container)
    {
        $loggerType = strtolower($config->getValue('logger', 'type'));

        if ($loggerType == 'fluent') {
            list($host, $port) = NameResolver::resolveHostAndPort($config->getValue('logger', 'host'));
            return DataLoggerFluent::getInstance($host, $port);
        } elseif ($loggerType == 'sqs') {
            try {
                $sqsI = $container->get(SqsInterface::class);
                $queueUrl = $config->getValue('logger', 'queue');
                return DataLoggerSqs::getInstance($sqsI->getClient(), $queueUrl);
            } catch (ContainerExceptionInterface $e) {
                throw new ConfigException("Can not create sqs logger");
            }
        }
        throw new ConfigException("logger.type is unknown type(${loggerType})");
    }
}