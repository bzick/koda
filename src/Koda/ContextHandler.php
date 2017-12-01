<?php

namespace Koda;

use Koda\Error\InvalidArgumentException;

/**
 * Data verification
 * (count 7, value >7, value <=8, count 1..4, file, date %Y-%m-%d, keyword)
 */
class ContextHandler implements \ArrayAccess
{
    public $injector;
    public $factory;
    public $context;

    protected $_data = [];


    public function setInjector(callable $injector)
    {
        $this->injector = $injector;

        return $this;
    }

    public function setFactory(callable $factory)
    {
        $this->factory = $factory;

        return $this;
    }

    /**
     * Set call context. Returns clone of the handler.
     *
     * @param object $ctx
     *
     * @return ContextHandler
     */
    public function setContext($ctx) : self {
        $clone = clone $this;
        $clone->context = $ctx;
        return $clone;
    }

    public function hasContext() : bool {
        return (bool) $this->context;
    }

    public function getContext() {
        return $this->context;
    }

    /**
     * Makes the injection
     * @param ArgumentInfo $info
     * @param mixed $value given value
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function injection(ArgumentInfo $info, $value = null)
    {
        try {
            return call_user_func($this->injector, $info, $value);
        } catch (\Exception $e) {
            throw Error::injectionFailed($info, $e);
        }
    }

    public function factory(ArgumentInfo $info, $value = null)
    {
        try {
            return call_user_func($this->factory, $info, $value);
        } catch (\Exception $e) {
            throw Error::factoryFailed($info, $e);
        }
    }

    public function __call($name, $params)
    {
        if ($this->context && method_exists($this->context, $name)) {
            return call_user_func_array([$this->context, $name], $params);
        }

        return true;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    public function unsignedFilter($value)
    {
        return $value >= 0;
    }

    /**
     * @param mixed $date
     * @param string $format
     *
     * @return bool
     */
    public function dateFilter($date, $format)
    {
        if (is_string($format)) {
            return \DateTime::createFromFormat($format, $date) !== false;
        } else {
            return strtotime($date) !== false;
        }
    }

    /**
     * Dummy validator, just for markers
     * @return bool
     */
    public function isFilter()
    {
        return true;
    }

    /**
     * Text string should be < 255 Bytes
     *
     * @param string $string
     *
     * @return bool
     */
    public function smallTextFilter($string)
    {
        return strlen($string) < 0x100; // max 255B
    }

    /**
     * Text string should be < 64 MiB
     *
     * @param string $text
     *
     * @return bool
     */
    public function textFilter($text)
    {
        return strlen($text) < 0x10000; // mx 64MiB
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    public function largeTextFilter($text)
    {
        return strlen($text) < 0x200000;  // 2MiB
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function positiveFilter($value)
    {
        return $value > 0;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function negativeFilter($value)
    {
        return $value < 0;
    }

    /**
     * Validate email
     * email -> test@dev.null
     * email extended -> test@dev.null
     * @param string $value
     * @param string $type
     *
     * @return bool
     */
    public function emailFilter($value, $type)
    {
        if ($type === "extended" && strpos($value, "<") !== false) {
            if (preg_match('/^(?:.*?)?<(.*?)>$/', $value, $matches)) {
                return filter_var($matches[1], FILTER_VALIDATE_EMAIL) !== false;
            }
        } else {
            return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        }

        return false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function domainFilter($value)
    {
        return !!preg_match('~([a-z0-9-]*\.)+[a-z0-9]+~', $value);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function urlFilter($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function ipFilter($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @param string $value
     *
     * @return int
     */
    public function keywordFilter($value)
    {
        return !!preg_match('!^[a-z0-9_-]*$!i', $value);
    }

    /**
     * @param string $value
     * @param mixed $len
     *
     * @return bool
     */
    public function valueFilter($value, $len)
    {
        if (is_array($len)) {
            return $value >= $len[0] && $value <= $len[1];
        } else {
            return $value == $len;
        }
    }

    /**
     * @param string $value
     * @param array|string $len
     *
     * @return bool
     */
    public function lengthFilter($value, $len)
    {
        if (is_array($len)) {
            return strlen($value) >= $len[0] && strlen($value) <= $len[1];
        } else {
            return strlen($value) == $len;
        }
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function callbackFilter($value)
    {
        return is_callable($value);
    }

    /**
     * Class name
     *
     * @param string $class
     *
     * @return bool
     */
    public function classNameFilter($class)
    {
        return class_exists($class);
    }

    /**
     * Must be valid file
     *
     * @param string $path
     *
     * @return bool
     */
    public function fileFilter($path)
    {
        return is_file(strval($path));
    }

    /**
     * Must be valid directory
     *
     * @param string $path
     *
     * @return bool
     */
    public function dirFilter($path)
    {
        return is_dir(strval($path));
    }

    /**
     * Limiting the number of elements in the array
     *
     * @param array $value
     * @param int|array $num the number of elements in the array
     *
     * @return bool
     */
    public function countFilterArg($value, $num)
    {
        if (is_array($num)) {
            return count($value) >= $num[0] && count($value) <= $num[1];
        } else {
            return count($value) == $num;
        }
    }

    /**
     * @param $value
     * @param string $pattern
     *
     * @return bool
     */
    public function maskFilter($value, $pattern)
    {
        return !!preg_match('~^[' . str_replace('~', '\~', $pattern) . ']*$~S', $value);
    }

    /**
     * @param $value
     * @param $pattern
     *
     * @return bool
     */
    public function regexpFilter($value, $pattern)
    {
        return (bool)preg_match($pattern, $value);
    }

    /**
     * @param $value
     * @param $pattern
     *
     * @return bool
     */
    public function likeFilter($value, $pattern)
    {
        return fnmatch($pattern, $value);
    }

    /**
     * @param $value
     * @param $variants
     *
     * @return bool
     */
    public function variantsFilter($value, $variants)
    {
        if (is_array($variants)) {
            return in_array($value, $variants);
        } elseif (is_callable($variants)) {
            return in_array($value, (array)call_user_func($variants));
        } else {
            return strpos($variants . " ", $value . " ") !== false;
        }
    }

    /**
     * @param mixed $value
     * @param callable $callback
     *
     * @return bool
     */
    public function optionFilter($value, $callback)
    {
        $options = call_user_func($callback, $value);

        return $options && isset($options[$value]);
    }

    /**
     * Whether a offset exists
     * @link  http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     *                      </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Offset to retrieve
     * @link  http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     *                      </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->_data[$offset] ?? null;
    }

    /**
     * Offset to set
     * @link  http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     *                      </p>
     * @param mixed $value  <p>
     *                      The value to set.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link  http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     *                      The offset to unset.
     *                      </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }
}