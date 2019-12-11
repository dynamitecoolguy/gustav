<?php


namespace Gustav\Common\Config;

use Gustav\Common\Exception\ConfigException;

/**
 * Config設定用クラス用インターフェイス.
 * ApplicationConfigの動作を変更する場合は、以下のパターンが考えられる.
 *   (1) ApplicationConfigを継承したクラスを作成するか、
 *   (2) このインターフェイスを実装した別のクラスを作成し、
 * DIコンテナに登録する.
 *
 * Interface ApplicationConfigInterface
 * @package Gustav\Common\Config
 */
interface ApplicationConfigInterface
{
    /**
     * 指定されたカテゴリ内のキーの値を取得する
     * @param string $category
     * @param string $key
     * @param string|null $default
     * @return string
     * @throws ConfigException
     */
    public function getValue(string $category, string $key, ?string $default = null): string;
}
