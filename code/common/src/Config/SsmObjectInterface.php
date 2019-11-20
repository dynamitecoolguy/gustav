<?php


namespace Gustav\Common\Config;

/**
 * Interface AbstractSsmObject
 * @package Gustav\Common\Config
 */
interface SsmObjectInterface
{
    /**
     * セットアップ用パラメータのセット
     * @param string[] $parameters
     */
    public function setUp(array $parameters): void;

    /**
     * @param string[] $keys
     * @return string[]
     */
    public function getParameters(array $keys): array;
}
