<?php

namespace Koda;


use Koda\Error;
use Koda\Error\CallableNotFoundException;

class MethodInfo extends CallableInfo {

    public $class;
    public $method;

	/**
	 * Scan method
	 * @param mixed $class class name or object
	 * @param string $method
	 * @return MethodInfo
	 * @throws CallableNotFoundException
	 */
    public static function scan($class, $method) {
        try {
            $me = new \ReflectionMethod($class, $method);
        } catch(\Exception $e) {
            throw Error::methodNotFound($class."::".$method);
        }
	    $info = new static;
	    $info->import($me);
	    return $info;
    }

	/**
	 * Import method from reflection
	 * @param \ReflectionMethod $method
	 * @return static
	 */
    public function import(\ReflectionMethod $method) {
	    $this->class  = $method->class;
	    $this->method = $method->name;
	    $this->name   = $method->class."::".$method->name;
	    $this->_importFromReflection($method);
    }

	/**
	 * Invoke method
	 * @param array $params
	 * @param Filter $filter
	 * @return mixed
	 * @throws \Koda\Error\TypeCastingException
	 */
	public function invoke(array $params, Filter $filter = null) {
		if(!$filter) {
			$filter = \Koda::getFilter($this->name);
		}
		$args = $this->filterArgs($params, $filter);
		return call_user_func_array(array($filter->context ?: $this->class, $this->method), $args);
	}
} 