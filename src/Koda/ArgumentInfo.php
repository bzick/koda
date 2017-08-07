<?php

namespace Koda;


use Koda\Error\TypeCastingException;

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

    public function getCallableName(): string
    {
//        return $this->;
    }

    /**
     * Convert value to required type (with validation if verify present)
     *
     * @param mixed $value
     * @param Handler $filter
     *
     * @return mixed
     * @throws Error\InvalidArgumentException
     * @throws TypeCastingException
     */
    public function filter($value, Handler $filter = null)
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
     * @param Handler $filter
     * @param mixed $index
     *
     * @throws TypeCastingException
     */
    public function toType(&$value, Handler $filter = null, $index = null)
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

    public function __debugInfo()
    {
        return [
            "name"        => $this->name,
            "type"        => $this->type,
            "class"       => $this->class_hint,
            "is_optional" => $this->optional,
            "is_variadic" => $this->variadic,
            "default"     => $this->default,
            "desc"        => $this->desc
        ];
    }
}