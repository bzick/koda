<?php

namespace Koda;


use Koda\Error;
use Koda\Error\TypeCastingException;

class ArgumentInfo
{
    const SCALAR  = 1;
    const COMPLEX = 2;

    /**
     * @var array of native types
     */
    public static $types = [
        "int"      => self::SCALAR,
        "bool"     => self::SCALAR,
        "float"    => self::SCALAR,
        "string"   => self::SCALAR,
        "array"    => self::COMPLEX,
        "NULL"     => self::COMPLEX,
        "resource" => self::COMPLEX,
        "callable" => self::COMPLEX,
    ];

    /**
     * @var array of native types with priorities
     */
    private static $_scalar = [
        "int"      => 9,
        "bool"     => 7,
        "float"    => 8,
        "string"   => 10,
//        "array"    => 6,
        "NULL"     => 1,
        "resource" => 5,
        "callable" => 10
    ];
    /**
     * Parameter name
     * @var string
     */
    public $name;
    /**
     * Parameter description
     * @var
     */
    public $desc;
    /**
     * Verification list
     * @var array[]
     */
    public $filters;
    /**
     * Expected multiple values
     * @var bool
     */
    public $multiple = false;
    /**
     * Type of expected value (native PHP type)
     * @var string
     */
    public $type;
    /**
     * Class name, if parameter expects object
     * @var string
     */
    public $class;
    /**
     * Is this optional parameter?
     * @var bool
     */
    public $optional = false;
    /**
     * Default value
     * @var
     */
    public $default;
    /**
     * Position in argument list of method (starts with 0)
     * @var int
     */
    public $position;

    /**
     * @var bool
     */
    public $inject = false;

    /**
     * @var CallableInfo
     */
    public $cb;

    public function __construct(CallableInfo $cb)
    {
        $this->cb = $cb;
    }

    public function __toString()
    {
        return $this->cb->name . "(\$" . $this->name . ")";
    }

    /**
     * Import information from reflection
     *
     * @param \ReflectionParameter $param
     *
     * @return static
     */
    public function import(\ReflectionParameter $param)
    {
        $this->name = $param->name;
        if (isset($this->cb->params[$param->name])) {
            $doc_info      = $this->cb->params[$param->name];
            $this->desc    = $doc_info["desc"];
            $this->filters = $doc_info["filters"];
        } else {
            $doc_info = false;
        }
        $this->optional = $param->isOptional();
        $this->default  = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        $this->position = $param->getPosition();

        if (isset($this->filters["inject"])) {
            $this->inject = $this->filters["inject"] ?: $param->name;
            unset($this->filters["inject"]);
        }

        if ($param->isArray()) {
            $this->multiple = true;
            $this->type     = null;
        }

        if ($c = $param->getClass()) {
            $this->type  = "object";
            $this->class = $c->name;
        } elseif ($doc_info) {
            $_type = $doc_info["type"];
            if (strpos($_type, "|")) { // multitype mark as mixed
                $this->type = null;

                return $this;
            }
            if (strpos($_type, "[")) { // multiple values
                $_type          = strstr($_type, "[", true);
                $this->multiple = true;
                if ($_type === "array") {
                    $this->type = "array";

                    return $this;
                }
            }
            if ($_type === "array") {
                $this->multiple = true;
                $this->type     = null;
            } elseif ($_type == "mixed") {
                $this->type = null;

                return $this;
            } elseif (isset(self::$_scalar[$_type])) {
                $this->type = $_type;
            } elseif ($_type == "self" && $this->cb->class) {
                $this->type  = "object";
                $this->class = $this->cb->class;
            } else {
                $_type       = ltrim($_type, '\\');
                $this->type  = "object";
                $this->class = $_type;
            }
        } else {
            $this->type = null;
        }

        return $this;
    }

    /**
     * Convert value to required type (with validation if verify present)
     *
     * @param mixed $value
     * @param Filter $filter
     *
     * @return mixed
     * @throws Error\InvalidArgumentException
     * @throws TypeCastingException
     */
    public function filter($value, Filter $filter = null)
    {
//        $type = gettype($value);
        $arg = $this;
        if ($this->multiple && !is_array($value)) {
            // todo strict mode
            $value = [$value];
//            throw Error::invalidType($this, $type);
        }
        if ($this->type) {
            if ($this->multiple) {
                foreach ($value as &$v) {
                    $arg->toType($v, $filter);
                }
            } else {
                $this->toType($value, $filter);
            }
        }
        if ($this->filters && $filter) {
            foreach ($this->filters as $method => $f) {
                if ($this->multiple) {
                    foreach ($value as $k => &$item) {
                        try {
                            if ($filter->{$method . "Filter"}($item, $f['args'], $this) === false) {
                                throw Error::filteringFailed($this, $method);
                            }
                        } catch (\Exception $e) {
                            throw Error::filteringFailed($this, $method, $e);

                        }
                    }
                } else {
                    try {
                        if ($filter->{$method . "Filter"}($value, $f['args'], $this) === false) {
                            throw Error::filteringFailed($this, $method);
                        }
                    } catch (\Exception $e) {
                        throw Error::filteringFailed($this, $method, $e);
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Type casting
     *
     * @param mixed $value
     * @param Filter $filter
     *
     * @throws TypeCastingException
     */
    public function toType(&$value, Filter $filter = null)
    {
        $type = gettype($value);
        switch ($this->type) {
            case "callable":
                if (!is_callable($value)) {
                    throw Error::invalidType($this, $type);
                }

                return;
            case "object":
                if (is_a($value, $this->class)) {
                    return;
                } elseif ($filter && $filter->factory) {
                    $value = $filter->factory($this, $value);
                    if (!is_a($value, $this->class)) {
                        throw Error::invalidType($this, $type);
                    }

                    return;
                } else {
                    throw Error::invalidType($this, $type);
                }
            case "array":
                if (!is_array($value)) {
                    throw Error::invalidType($this, $type);
                }

                return;
        }
        if ($type == "object" || $type == "array") {
            throw Error::invalidType($this, $type);
        }
        switch ($this->type) {
            case "int":
            case "float":
                if (!is_numeric($value)) {
                    throw Error::invalidType($this, $type);
                } else {
                    settype($value, $this->type);
                }
                break;

            default:
                settype($value, $this->type);
        }
    }
}