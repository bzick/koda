<?php

namespace Koda;

class ArgumentInfo extends VariableInfoAbstract
{
    /**
     * Verification list
     * @var array[]
     */
    public $filters;
    /**
     * Is variadic parameter?
     * @var bool
     */
    public $variadic = false;

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
     * @var CallableInfoAbstract
     */
    public $cb;

    public function __construct(CallableInfoAbstract $cb)
    {
        $this->cb = $cb;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getName($index = null) : string
    {
        if ($index === null) {
            return $this->cb . "(\$" . $this->name . ")";
        } elseif(strlen($index) < 128 || !is_string($index)) {
            return $this->cb . "(\$" . $this->name . "[" . $index . "])";
        } else {
            return $this->cb . "(\$" . $this->name . "[" . substr($index, 0, 128) . "...])";
        }
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
        $this->optional = $param->isOptional();
        $this->variadic = $param->isVariadic();
        $this->default  = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        if ($param->isDefaultValueAvailable()) {
            $this->default_expr = $param->isDefaultValueConstant() ? $param->getDefaultValueConstantName() : null;
        }
        $this->position = $param->getPosition();
        $this->callable = $param->getDeclaringFunction()->getName();
        if ($param->hasType()) {
            $this->type = $param->getType();
        }



        if ($param->isArray()) {
            $this->multiple = true;
            $this->type     = null;
        }

        if ($c = $param->getClass()) {
            $this->type       = "object";
            $this->class_hint = $c->name;
        }
        if (isset($this->cb->params[$param->name])) {
            if ($this->cb instanceof MethodInfo) {
                $this->parseHint(
                    $this->cb->params[$param->name],
                    $this->cb->getClass(),
                    true
                );
            } else {
                $this->parseHint(
                    $this->cb->params[$param->name],
                    null,
                    true
                );
            }
        }
        if (isset($this->filters["inject"])) {
            $this->inject = $this->filters["inject"]["args"] ?: $param->name;
            unset($this->filters["inject"]);
        }

        return $this;
    }

    public function getClassName(): string
    {
        return $this->class_hint;
    }

    public function getClass(): ClassInfo
    {
        return new ClassInfo($this->name);
    }

    public function __debugInfo()
    {
        return [
            "name"        => $this->name,
            "type"        => $this->type,
            "class"       => $this->class_hint,
            "is_optional" => $this->optional,
            "is_variadic" => $this->variadic,
            "default"     => $this->default,
            "desc"        => $this->desc,
            "filters"     => $this->filters,
        ];
    }
}