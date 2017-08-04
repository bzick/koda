<?php

namespace Koda;


use Koda\Error;
use Koda\Error\CallableNotFoundException;

class MethodInfo extends CallableInfoAbstract
{

	public $method;
	public $class;

	/**
	 * Scan method
	 *
	 * @param mixed $class class name or object
	 * @param string $method
	 *
	 * @return MethodInfo
	 * @throws CallableNotFoundException
	 */
	public static function scan($class, $method)
	{
		try {
			$me = new \ReflectionMethod($class, $method);
		} catch (\Exception $e) {
			throw Error::methodNotFound($class . "::" . $method);
		}
		$info = new static;
		$info->import($me);
		$info->class = is_string($class) ? $class : get_class($class);

		return $info;
	}

	/**
	 * Import method from reflection
	 *
	 * @param \ReflectionMethod $method
	 *
	 * @return static
	 */
	public function import(\ReflectionMethod $method)
	{
		$this->class  = $method->class;
		$this->method = $method->name;
		$this->name   = $method->class . "::" . $method->name;
		$this->_importFromReflection($method);

		return $this;
	}

	public function getClass() : ClassInfo {
	    return new ClassInfo($this->class);
    }

	/**
	 * Invoke method
	 *
	 * @param array $params
	 * @param Filter $filter
	 *
	 * @return mixed
	 * @throws \Koda\Error\TypeCastingException
	 */
	public function invoke(array $params, Filter $filter = null)
	{
		if (!$filter) {
			$filter = \Koda::getFilter($this->name);
		}
		$args = $this->filterArgs($params, $filter);

		return call_user_func_array([$filter->context ?: $this->class, $this->method], $args);
	}


    public function __debugInfo()
    {
        return [
            "short" => $this->method,
            "return" => $this->return,
            "arguments" => $this->args,
            "options" => $this->options
        ];
    }
}