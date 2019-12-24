<?php


namespace Gustav\Common\Exception;

/**
 * Networkに関する例外
 * Class NetworkException
 * @package Gustav\Common\Exception
 */
class NetworkException extends GustavException
{
    const HASH_IS_INCONSISTENCY = 1;
    const MESSAGE_IS_TOO_SHORT = 2;
    const DISPATCHER_TABLE_HAS_ILLEGAL_RECORD = 3;
    const DISPATCHER_TABLE_INTERFACE_IS_NOT_REGISTERED = 4;
    const EXECUTOR_IS_NOT_REGISTERED = 5;
    const CLASSES_IS_NOT_SAME = 6;
    const EXECUTOR_HAS_EXCEPTION = 7;
}