<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;
use Koda\Error\TypeCastingException;

abstract class VariableInfoAbstract  implements \JsonSerializable
{
    const SCALAR  = 1;
    const COMPLEX = 2;

    /**
     * array of native types with priorities
     */
    const SCALARS = [
        "int"      => 9,
        "bool"     => 7,
        "float"    => 8,
        "string"   => 10,
        "NULL"     => 1,
        "resource" => 5,
        "callable" => 10
    ];

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
        "void"     => self::COMPLEX,
        "resource" => self::COMPLEX,
        "callable" => self::COMPLEX,
        "object"   => self::COMPLEX,
    ];

    /**
     * Parameter name
     * @var string
     */
    public $name = "";
    /**
     * Parameter description
     * @var
     */
    public $desc = "";
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
    public $class_hint = "";
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
    public $default_expr;
    public $filters = [];

    abstract public function __debugInfo();
    abstract public function getName($index = null);

    /**
     * @param array|string $hint_info
     * @param string $scope
     * @param bool $parse_filters
     *
     * @throws InvalidArgumentException
     */
    public function parseHint($hint_info, string $scope = null, bool $parse_filters = true) {

        if (is_string($hint_info)) {
            if (preg_match('/^(.*?)\s+\$(\w+)\s*(?:\(([^\)]+)\))?/mS', $hint_info, $matches)) {
                $inf = [
                    "type" => $matches[1] ?? '',
                    "name" => $matches[2] ?? '',
                    "filters" => $matches[3] ?? '',
                    "desc" => trim(substr($hint_info, strlen($matches[0])))
                ];
            } else {
                $inf = false;
            }
        } elseif (is_array($hint_info)) {
            $inf = $hint_info + ["type" => "", "name" => "", "filters" => "", "desc" => ""];
        } else {
            throw new InvalidArgumentException("Invalid doc-block of $this");
        }
        if ($inf) {
            if (!$this->name && $inf["name"]) {
                $this->name = $inf["name"];
            }
            if (!$this->hasType()) {
                $this->setType($inf["type"], $scope);
            }

            if($inf["filters"]) {
                if($parse_filters) {
                    $this->desc .= $inf["desc"];
                    $this->filters = ParseKit::parseDoc($inf["filters"]);
                } else {
                    $this->desc .= $inf["filters"] . ' ' . $inf["desc"];
                }
            } else {
                $this->desc .= $inf["desc"];
            }
        }
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->__debugInfo();
    }

    public function getDescription() : string
    {
        return $this->desc;
    }

    public function setDescription(string $desc)
    {
        $this->desc = $desc;
        return $this;
    }

    public function hasType()
    {
        return (bool)$this->type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType(string $type, string $class_name = null) : self
    {
        $this->type = $type;
        if (strpos($this->type, "|")) { // multitype mark as mixed
            $this->type = null;
        } else if (strpos($this->type, "[")) { // multiple values
            $this->type = strstr($this->type, "[", true);
            $this->multiple = true;
        }
        if ($this->type == "array") {
            $this->type = null;
            $this->multiple = true;
        } else if ($this->type == "mixed") {
            $this->type  = null;
        } else if ($this->type == "self" && $class_name) {
            $this->type       = "object";
            $this->class_hint = $class_name;
        } else if (!isset(self::SCALARS[$this->type])) { // select class
            if ($this->type{0} === "\\") { // absolute class name
                $this->class_hint = ltrim($this->type, '\\');
            } else if($class_name) {

                $this->class_hint = ltrim($class_name->namespace . '\\' . $this->type, '\\');
            }
            $this->type = "object";
        }
        return $this;
    }

    /**
     * @param $value
     */
    public function setDefaultValue($value)
    {
        $this->default = $value;
    }


    /**
     * Convert value to required type (with validation if filter present)
     *
     * @param mixed $value
     * @param ContextHandler $filter
     *
     * @return mixed
     * @throws Error\InvalidArgumentException
     * @throws TypeCastingException
     */
    public function filter($value, ContextHandler $filter = null)
    {
        $arg = $this;
        if ($this->multiple && !is_array($value)) {
            // add strict mode ?
            $value = [$value];
        }
        if ($this->type) {
            if ($this->multiple) {
                foreach ($value as $index => &$v) {
                    $arg->toType($v, $filter, $index);
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
     * @param ContextHandler $filter
     * @param mixed $index
     *
     * @throws TypeCastingException
     */
    public function toType(&$value, ContextHandler $filter = null, $index = null)
    {
        $type = gettype($value);
        switch ($this->type) {
            case "callable":
                if (!is_callable($value)) {
                    throw Error::invalidType($this, $value, $index);
                }

                return;
            case "object":
                if (is_a($value, $this->class_hint)) {
                    return;
                } elseif ($filter && $filter->factory) {
                    $value = $filter->factory($this, $value);
                    if (!is_a($value, $this->class_hint)) {
                        throw Error::invalidType($this, $value, $index);
                    }

                    return;
                } else {
                    throw Error::invalidType($this, $value, $index);
                }
            case "array":
                if (!is_array($value)) {
                    throw Error::invalidType($this, $value, $index);
                }

                return;
        }
        if ($type == "object" || $type == "array") {
            throw Error::invalidType($this, $value, $index);
        }
        switch ($this->type) {
            case "int":
            case "float":
                if (!is_numeric($value)) {
                    throw Error::invalidType($this, $value, $index);
                } else {
                    settype($value, $this->type);
                }
                break;

            default:
                settype($value, $this->type);
        }
    }

}