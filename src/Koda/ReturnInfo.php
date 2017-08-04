<?php

namespace Koda;


class ReturnInfo extends VariableInfoAbstract
{

    public $cb;
    public $allow_null = false;
    public $class;

    public function __construct(CallableInfoAbstract $cb)
    {
        $this->cb = $cb;
    }

    public function import(\ReflectionType $type = null)
    {
        if($type) {
            if ($type->isBuiltin()) {
                $this->type = strval($type);
            } else {
                $this->type = "object";
                $this->class_hint = strval($type);
            }
            $this->allow_null = $type->allowsNull();
        }
        if($this->cb->hasOption("return")) {
            $this->parseHint(
                $this->cb->getOption("return"),
                $this->cb->hasClass() ? $this->cb->hasClass() : null,
                false
            );
        }
    }

    public function hasType()
    {
        return (bool)$this->type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getClass()
    {
        return $this->class;
    }

    public function getDescription()
    {
        return $this->desc;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function __debugInfo()
    {
        if ($this->class) {
            return [
                "type" => $this->type,
                "class" => $this->class,
                "desc" => $this->desc,
                "allow_null" => $this->allow_null
            ];
        } else {
            return [
                "type" => $this->type,
                "desc" => $this->desc,
                "allow_null" => $this->allow_null
            ];
        }
    }

}