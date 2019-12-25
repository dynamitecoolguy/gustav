<?php


namespace Gustav\Common\Model;

use Exception;
use Gustav\Common\Exception\ModelException;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

/**
 * ModelInterfaceを実装するオブジェクトの基底クラス.
 * setter/getterの自動化を行っている.
 *
 * setHoge/getHoge(or isHoge)が呼び出されたときには以下の処理を行う.
 *   (1) そのメソッドがあれば、それがそのまま呼び出される
 *   (2) プロパティhogeがあれば、それに対して読み書きする
 *   (3) ModelExceptionエラーが発生する
 *
 * またコンストラクタでは、['key'=>'value', ....]の引数を受け取ることができる.
 * このときは、以下のような処理になる.
 *   (1) メソッドsetKeyがあればそれを呼び出す
 *   (2) プロパティkeyがあれば、それに値をセットする
 *   (3) ModelExceptionエラーが発生する
 *
 * なお、getHoge/setHogeの形式以外にも、get('hoge'), set('hoge', $value)の形式も使用できる.
 * Class AbstractModel
 * @package Gustav\Common\Model
 */
class AbstractModel implements ModelInterface
{
    /**
     * AbstractModel constructor.
     * クラス説明にあるように、$paramsがnullで無い場合には、パラメータのセットが行われる.
     * @param array|null $params
     * @throws ModelException
     */
    public function __construct(?array $params = null)
    {
        if (!is_null($params)) {
            $ref = new ReflectionObject($this);
            foreach ($params as $key => $value) {
                $this->setPropertyInternal($ref, $key, $value);
            }
        }
    }

    /**
     * constructor用のプロパティのセット
     * 'hoge'に対して、setHogeがあればそれを呼び、なければ、プロパティ'hoge'をセットする。両方なければエラーになる
     * @param ReflectionObject $ref
     * @param string $key
     * @param mixed $value
     * @throws ModelException
     */
    private function setPropertyInternal(ReflectionObject $ref, string $key, $value): void
    {
        $methodName = 'set' . ucfirst($key);
        if ($ref->hasMethod($methodName)) {
            try {
                $m = $ref->getMethod($methodName);
                $m->setAccessible(true);
                $m->invoke($this, $value);
            } catch (Exception $e) {
                throw new ModelException(
                    "Method (${methodName}) call but failed.",
                    ModelException::SETTER_IS_INACCESSIBLE,
                    $e);
            }
        } else {
            try {
                $p = $ref->getProperty($key);
            } catch (ReflectionException $e) {
                throw new ModelException(
                    "Property (${key}) is inaccessible",
                    ModelException::PROPERTY_IS_INACCESSIBLE,
                    $e);
            }
            $p->setAccessible(true);
            $p->setValue($this, $value);
        }
    }

    /**
     * Magic setter/getter
     * setHoge/getHoge用のマジックメソッド. クラスの説明参照
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ModelException
     */
    public function __call($name, $arguments)
    {
        $h = $name[0];
        if ($h === 's' && strpos($name, 'set') === 0) {
            $property = lcfirst(substr($name, 3));
        } elseif ($h === 'g' && strpos($name, 'get') === 0) {
            $property = lcfirst(substr($name, 3));
        } elseif ($h === 'i' && strpos($name, 'is') === 0) {
            $property = lcfirst(substr($name, 2));
        } else {
            // setHoge/getHoge以外の形のメソッドはModelExceptionエラーになる
            throw new ModelException(
                "Method ${name} not exists",
                ModelException::NO_SUCH_METHOD
            );
        }
        $ref = $this->getReflectionProperty($property);
        if ($h === 's') { // setter
            $ref->setValue($this, $arguments[0]);
            return null;
        } elseif ($h === 'g') { // getter
            return $ref->getValue($this);
        } else { // is
            return boolval($ref->getValue($this));
        }
    }

    /**
     * getHogeではなくget('hoge')のように呼び出すことができる
     * @param string $name
     * @return mixed
     * @throws ModelException
     */
    public function get(string $name)
    {
        $ref = $this->getReflectionProperty($name);
        return $ref->getValue($this);
    }

    /**
     * setHoge($value)ではなくset('hoge', $value)のように呼び出すことができる
     * @param string $name
     * @param $value
     * @throws ModelException
     */
    public function set(string $name, $value): void
    {
        $ref = $this->getReflectionProperty($name);
        $ref->setValue($this, $value);
    }

    /**
     * ReflectionPropertyを返す内部メソッド
     * @param string $name
     * @return ReflectionProperty
     * @throws ModelException
     */
    private function getReflectionProperty(string $name): ReflectionProperty
    {
        $ref = new ReflectionObject($this);
        try {
            $p = $ref->getProperty($name);
        } catch (ReflectionException $e) {
            throw new ModelException(
                "Property (${name}) is inaccessible",
                ModelException::PROPERTY_IS_INACCESSIBLE,
                $e);
        }
        $p->setAccessible(true); // public以外書き込みに失敗するので強制書き込み可能にする
        return $p;
    }
}
