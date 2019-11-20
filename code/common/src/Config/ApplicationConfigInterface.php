<?php


namespace Gustav\Common\Config;

/**
 * Interface ApplicationConfigInterface
 * @package Gustav\Common\Config
 */
interface ApplicationConfigInterface
{
    /**
     * 指定されたカテゴリ内のキーの値を取得する
     * @param string $category
     * @param string $key
     * @return string
     */
    public function getValue(string $category, string $key): string;
}