<?php

namespace Koda;


class PropertyInfo extends VariableInfoAbstract
{
    use OptionsTrait;

    /**
     * @var string
     */
    public $class;

    public function __construct(ClassInfo $class)
    {
        $this->class = $class->name;
    }

    public function import(\ReflectionProperty $prop, \ReflectionClass $rc = null)
    {
        $this->class    = $prop->class;
        $this->name     = $prop->getName();
        $this->optional = $prop->isDefault();
        if ($prop->isStatic()) {
            $this->default  = $prop->isDefault() ? $prop->getValue() : null;
        } else {
            if (!$rc) {
                $rc = new \ReflectionClass($this->class);
            }
            $this->default  = $rc->getDefaultProperties()[$this->name];
        }
        $doc = $prop->getDocComment();
        if ($doc) {
            $this->options = ParseKit::parseDocBlock($doc);
            if ($this->hasOption('var')) {
                $parsed = preg_split('/\s+/S', $this->getOption('var'), 2);
                $this->parseHint([
                    "type" => $parsed[0],
                    "name" => $this->name,
                    "desc" => ($parsed[1] ?? '')
                ], new ClassInfo($this->name));
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