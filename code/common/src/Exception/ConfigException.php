<?php


namespace Gustav\Common\Exception;

/**
 * Config設定が不足、値エラーなどのConfigurationに関する例外
 * Class ConfigException
 * @package Gustav\Common\Exception
 */
class ConfigException extends GustavException
{
    const NO_SUCH_CATEGORY_OR_KEY_IN_CONFIG = 1;
    const MODEL_SERIALIZER_IS_INVALID = 2;
    const DATA_LOGGER_IS_INVALID = 3;
}