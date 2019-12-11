<?php


namespace Gustav\Common\Model;

use Gustav\Common\Exception\ModelException;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;

class AbstractModel implements ModelInterface
{
    /**
     * AbstractModel constructor.
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
     * プロパティのセット
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
            } catch (ReflectionException $e) {
                throw new ModelException("Method (${methodName}) could not be accessed", 0, $e);
            }
        } elseif ($ref->hasProperty($key)) {
            try {
                $p = $ref->getProperty($key);
            } catch (ReflectionException $e) {
                throw new ModelException("Property (${key}) could not be accessed", 0, $e);
            }
            $p->setAccessible(true);
            $p->setValue($this, $value);
        } else {
            throw new ModelException("Model has no such property(${key})");
        }
    }

    /**
     * Magic setter/getter
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ModelException
     */
    public function __call($name, $arguments)
    {
        $h = $name[0];
        if ($h === 's' && strpos($name, 'set') === 0) {
            $isSetter = true;
        } elseif ($h === 'g' && strpos($name, 'get') === 0) {
            $isSetter = false;
        } else {
            throw new ModelException("Method ${name} not exists");
        }
        $property = lcfirst(substr($name, 3));
        $ref = $this->getReflectionProperty($property);
        if ($isSetter) {
            $ref->setValue($this, $arguments[0]);
            return null;
        } else {
            return $ref->getValue($this);
        }
    }

    /**
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
     * @param string $name
     * @return ReflectionProperty
     * @throws ModelException
     */
    private function getReflectionProperty(string $name): ReflectionProperty
    {
        $ref = new ReflectionObject($this);
        if (!$ref->hasProperty($name)) {
            throw new ModelException("Model has no such property(${name})");
        }
        try {
            $p = $ref->getProperty($name);
        } catch (ReflectionException $e) {
            throw new ModelException("Property (${name}) could not be accessed", 0, $e);
        }
        $p->setAccessible(true);
        return $p;
    }
}
