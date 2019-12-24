<?php


namespace Gustav\Common\Log;


use Gustav\Common\Adapter\SqsInterface;
use Gustav\Common\Config\ApplicationConfigInterface;
use Gustav\Common\Exception\ConfigException;
use Gustav\Common\Network\NameResolver;
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
            // FluentLogger
            list($host, $port) = NameResolver::resolveHostAndPort($config->getValue('logger', 'host'));
            return DataLoggerFluent::getInstance($host, $port);
        } elseif ($loggerType == 'sqs') {
            // SQSLogger
            $sqsI = $container->get(SqsInterface::class);
            $queueUrl = $config->getValue('logger', 'queue');
            return DataLoggerSqs::getInstance($sqsI->getClient(), $queueUrl);
        }
        throw new ConfigException(
            "Data logger(logger.type=${loggerType}) is invalid",
            ConfigException::DATA_LOGGER_IS_INVALID
        );
    }
}