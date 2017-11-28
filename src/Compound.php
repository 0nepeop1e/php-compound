<?php
/**
 * Created by PhpStorm.
 * User: engshun
 * Date: 10/26/17
 * Time: 9:30 PM
 */

namespace onepeople\utils;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use TypeError;


class Compound implements ArrayAccess, IteratorAggregate, Countable
{
    protected $__data = [];
    protected $__present;

    /**
     * Variable constructor.
     * @param $value mixed
     */
    public function __construct($value = null)
    {
        $this->__present = func_num_args() > 0;
        $this->set($value);
    }

    /**
     * @param $value
     * @throws TypeError
     */
    public function set($value)
    {
        if (!is_scalar($value) && !is_null($value)) {
            if (is_object($value)) {
                $value = (array)$value;
            } else if (!is_array($value)) {
                throw new TypeError("Unexpected type.");
            }
        }
        $val = &$this->internalValue();
        $this->__present = true;
        if (is_array($value)) {
            $val = [];
            foreach ($value as $k => $v) {
                $this[$k]->set($v);
            }
            return;
        }
        $val = $value;
    }

    /**
     * @return mixed
     */
    protected function &internalValue()
    {
        return $this->__data;
    }

    public function __get($name)
    {
        return $this[$name];
    }

    public function __set($name, $value)
    {
        return $this[$name] = $value;
    }

    public function __unset($name)
    {
        unset($this[$name]);
    }

    public function __isset($name)
    {
        return isset($this[$name]);
    }

    /**
     * @return Compound
     */
    public function getParent()
    {
        return null;
    }

    /**
     * @param $nonExist
     * @return mixed
     */
    public function get($nonExist = null)
    {
        return $this->isPresent() ?
            $this->internalValue() :
            $nonExist;
    }

    /**
     * @param ?string $nonString
     * @return string
     */
    public function getString($nonString = null){
        return $this->isString() ?
            $this->internalValue() :
            $nonString;
    }

    /**
     * @param int $nonInt
     * @return int
     */
    public function getInt($nonInt = 0){
        $val = &$this->internalValue();
        $filtered = filter_var($val, FILTER_VALIDATE_INT);
        return $filtered === false ? $nonInt : $filtered;
    }

    /**
     * @param float $nonFloat
     * @return float
     */
    public function getFloat($nonFloat = 0.0){
        $val = &$this->internalValue();
        $filtered = filter_var($val, FILTER_VALIDATE_FLOAT);
        return $filtered === false ? $nonFloat : $filtered;
    }

    /**
     * @param bool $nonBool
     * @return bool
     */
    public function getBool($nonBool = null){
        if(!$this->isPresent())
            return $nonBool;
        $val = &$this->internalValue();
        $bool =  filter_var($val,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE);
        return $bool === null ? $nonBool : $bool;
    }

    /**
     * @return bool
     */
    public function isPresent()
    {
        return $this->__present;
    }

    /**
     * @return bool
     */
    public function isString()
    {
        return is_string($this->internalValue());
    }

    /**
     * @return bool
     */
    public function isInt()
    {
        return $this->getInt(null) !== null;
    }

    /**
     * @return bool
     */
    public function isFloat()
    {
        return $this->getFloat(null) !== null;
    }

    /**
     * @return bool
     */
    public function isBool()
    {
        return $this->getBool() !== null;
    }

    /**
     * @return bool
     */
    public function isAssociative()
    {
        return $this->isArray() &&
            array_keys($this->internalValue()) !==
            range(0, $this->count() - 1);
    }

    /**
     * @return bool
     */
    public function isArray()
    {
        return is_array($this->internalValue());
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        if (!is_array($this->internalValue())) {
            return 0;
        }
        return count($this->internalValue());
    }

    /**
     * @return bool
     */
    public function isSequential()
    {
        return $this->isArray() &&
            array_keys($this->internalValue()) ===
            range(0, $this->count() - 1);
    }

    /**
     *
     */
    public function delete()
    {
        $internal = &$this->internalValue();
        $internal = null;
        $this->__present = false;
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        if (!is_array($internal = &$this->internalValue())) {
            return false;
        }
        return key_exists($offset, $internal);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return Compound
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        $proxy_class = static::proxyClass();
        return new $proxy_class($this, $offset);
    }

    /**
     * @return mixed
     */
    public static function proxyClass()
    {
        return CompoundProxy::class;
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0.
     * @throws
     */
    public function offsetSet($offset, $value)
    {
        if (!is_scalar($value)) {
            if (is_object($value)) {
                $value = (array)$value;
            } else if (!is_array($value)) {
                throw new TypeError("Unexpected type.");
            }
        }
        $internal = &$this->internalValue();
        if (!is_array($internal)) {
            $internal = [];
        }
        if (is_null($offset)) {
            $internal[] = null;
            end($internal);
            $this[key($internal)]->set($value);
            rewind($internal);
        } else {
            $this[$offset]->set($value);
        }
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->internalValue()[$offset]);
    }

    /**
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        if (is_array($internal = &$this->internalValue())) {
            foreach ($internal as $k => $v) {
                yield $k => $this[$k];
            }
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->internalValue();
    }
}