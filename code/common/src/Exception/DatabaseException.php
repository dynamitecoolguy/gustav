<?php


namespace Gustav\Common\Exception;

/**
 * Database操作に関する例外
 * Class DatabaseException
 * @package Gustav\Common\Exception
 */
class DatabaseException extends GustavException
{
    const CONNECTION_FAILED = 1;
    const PREPARING_FAILED = 2;
    const BUFFERED_MODE_FAILED = 3;
    const EXECUTION_FAILED = 3;
    const TRANSACTION_FAILED = 4;
    const STATEMENT_COULD_NOT_BE_PREPARED = 5;
    const BIND_ERROR = 6;
    const STATEMENT_IS_ILLEGAL = 7;
    const DATABASE_IS_SLAVE = 8;
    const TRANSACTION_NESTED = 9;
}