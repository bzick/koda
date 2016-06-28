<?php

namespace Koda;


use Koda\Error\CallableNotFoundException;

class FunctionInfo extends CallableInfo
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
    public static function scan($name)
    {
        try {
            $fe = new \ReflectionFunction($name);
        } catch (\Exception $e) {
            throw Error::functionNotFound($name);
        }
        $info = new static;
        $info->import($fe);

        return $info;
    }

    public function import(\ReflectionFunction $function)
    {
        $this->name      = $function->name;
        $this->function  = $function->getShortName();
        $this->namespace = $function->getNamespaceName();
        $this->_importFromReflection($function);
    }

    /**
     * Invoke function
     *
     * @param array $params
     * @param Filter $filter
     *
     * @return mixed
     * @throws \Koda\Error\TypeCastingException
     */
    public function invoke(array $params, Filter $filter)
    {
        $args = $this->filterArgs($params, $filter);

        return call_user_func_array($this->name, $args);
    }

}