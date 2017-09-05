<?php

namespace Koda;


use Koda\Error\CallableNotFoundException;

class FunctionInfo extends CallableInfoAbstract
{

    public $function;
    /**
     * Scan function entry
     *
     * @param mixed $name The name of the function to reflect or a closure.
     *
     * @return self
     * @throws CallableNotFoundException
     */
    public function scan($name)
    {
        try {
            $fe = new \ReflectionFunction($name);
        } catch (\Exception $e) {
            throw Error::functionNotFound($name);
        }
        $this->import($fe);

        return $this;
    }

    public function import($function)
    {
        if (!$function instanceof \ReflectionFunction) {
            try {
                $function = new \ReflectionFunction($function);
            } catch (\Throwable $e) {
                throw Error::functionNotFound($function);
            }
        }
        $this->name      = $function->name;
        $this->function  = $function->getShortName();
        $this->namespace = $function->getNamespaceName();
        $this->_importFromReflection($function);
    }

    /**
     * Invoke function
     *
     * @param array $params
     * @param Handler $filter
     *
     * @return mixed
     * @throws \Koda\Error\TypeCastingException
     */
    public function invoke(array $params, Handler $filter)
    {
        $args = $this->filterArgs($params, $filter);

        return call_user_func_array($this->name, $args);
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function __debugInfo()
    {
        return [
            "name" => $this->function,
            "return" => $this->return,
            "arguments" => $this->args,
            "options" => $this->options
        ];
    }
}