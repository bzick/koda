<?php

namespace Koda;


class PropertyInfo extends VariableInfoAbstract
{

    /**
     * @var string
     */
    public $class;

    public function __construct(ClassInfo $class)
    {
        $this->class = $class->name;
    }

    public function import(\ReflectionProperty $prop)
    {
        $this->class    = $prop->class;
        $this->name     = $prop->getName();
        $this->optional = $prop->isDefault();
        $this->default  = $prop->isDefault() ? $prop->getValue() : null;
        $doc = $prop->getDocComment();
        if ($doc) {
            $options = ParseKit::parseDocBlock($doc);
            if (isset($options["var"])) {

            }
        }
    }

    public function __debugInfo()
    {
        return [
            "name"        => $this->name,
            "type"        => $this->type,
            "class"       => $this->class_hint,
            "is_optional" => $this->optional,
            "default"     => $this->default,
            "desc"        => $this->desc
        ];
    }
}