<?php


namespace Gustav\Common\Config;


class DummySsmObject implements SsmObjectInterface
{
    public function getParameters(array $keys): array
    {
        return array_reduce($keys, function($accumulator, $key) {
            $accumulator[$key] = "${key}_VALUE";
            return $accumulator;
        }, []);
    }

    /**
     * セットアップ用パラメータのセット
     * @param string[] $parameters
     */
    public function setUp(array $parameters): void
    {
        // do nothing
    }
}