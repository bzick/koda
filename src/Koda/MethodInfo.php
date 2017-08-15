<?php

namespace Koda;


use Koda\Error;
use Koda\Error\BaseException;
use Koda\Error\CallableNotFoundException;
use Koda\Error\InvalidArgumentException;

class MethodInfo extends CallableInfoAbstract
{

	public $method = '';
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
		$class = is_string($class) ? $class : get_class($class);
		$info = new static($class);
		$info->import($me);

		return $info;
	}

	public function __construct(string $class_name) {
        $this->class  = $class_name;
    }

    public function getMethodName() : string {
        return $this->method;
    }

    public function setMethodName(string $name) {
        $this->method = $name;
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
		$this->name   = $method->name;
		$this->method = $method->class . "::" . $method->name;
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
	 * @param Handler $filter
	 *
	 * @return mixed
	 * @throws BaseException
	 */
	public function invoke(array $params, Handler $filter = null)
	{
		if (!$filter) {
			$filter = \Koda::getFilter($this->name);
		}
		$args = $this->filterArgs($params, $filter);
        try {
            return call_user_func_array([$filter->context ?: $this->class, $this->method], $args);
        } catch (BaseException $error) {
	        throw $error;
        } catch (\TypeError $error) {
            throw new InvalidArgumentException("Some of the arguments were not converted to the correct type", 0, $error);
        } catch (\ArgumentCountError $error) {
	        throw new InvalidArgumentException("Too few arguments are passed", 0, $error);
        } catch (\Throwable $error) {
            throw Error::methodCallFailed($this, $error);
        }
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

    public function __toString()
    {
        return $this->method;
    }
}