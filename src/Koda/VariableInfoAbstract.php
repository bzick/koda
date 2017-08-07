<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

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
//        "array"    => 6,
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


    /**
     * @param array|string $hint_info
     * @param ClassInfo $scope
     * @param bool $parse_filters
     *
     * @throws InvalidArgumentException
     */
    public function parseHint($hint_info, ClassInfo $scope = null, bool $parse_filters = true) {

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
            if (!$this->type && $inf["type"]) {
                $this->type = $inf["type"];
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
                } else if ($this->type == "self" && $scope) {
                    $this->type       = "object";
                    $this->class_hint = $scope->name;
                } else if (!isset(self::SCALARS[$this->type])) { // select class
                    if ($this->type{0} === "\\") { // absolute class name
                        $this->class_hint = ltrim($this->type, '\\');
                    } else if($scope) {
                        $this->class_hint = ltrim($scope->namespace . '\\' . $this->type, '\\');
                    }
                    $this->type = "object";
                }
            }

            if($inf["filters"]) {
                if($parse_filters) {
                    $this->desc .= $inf["desc"];
                    $this->filters = Filter::parseDoc($inf["filters"]);
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
    function jsonSerialize()
    {
        return $this->__debugInfo();
    }

}