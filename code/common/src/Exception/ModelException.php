<?php


namespace Gustav\Common\Exception;

/**
 * データモデルに関する例外
 * Class ModelException
 * @package Gustav\Common\Exception
 */
class ModelException extends GustavException
{
    const PACK_TYPE_IS_DUPLICATED = 1;
    const PACK_TYPE_IS_UNREGISTERED = 2;
    const NO_SUCH_CLASS = 3;
    const SETTER_IS_INACCESSIBLE = 4;
    const PROPERTY_IS_INACCESSIBLE = 5;
    const NO_SUCH_PROPERTY = 6;
    const NO_SUCH_METHOD = 7;
    const CLASS_HAS_NOT_ADAPTED_INTERFACE = 8;
    const REFLECTION_EXCEPTION_OCCURRED = 9;
}