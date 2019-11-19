<?php


namespace Gustav\Common\Config;


class DummySsmObject extends AbstractSsmObject
{
    public function getParameters(array $keys): array
    {
        return array_reduce($keys, function($accumulator, $key) {
            $accumulator[$key] = "${key}_VALUE";
            return $accumulator;
        }, []);
    }
}