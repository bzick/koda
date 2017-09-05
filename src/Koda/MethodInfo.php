<?php

namespace Koda;


use Koda\Error;
use Koda\Error\BaseException;
use Koda\Error\CallableNotFoundException;
use Koda\Error\InvalidArgumentException;

class MethodInfo extends CallableInfoAbstract
{

	public $method = '';

	public function __construct($class)
    {
	    if (is_string($class)) {
            $this->class  = new ClassInfo($class);
        } else {
            $this->class  = $class;
        }
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
     * @param \ReflectionMethod|string $method
     *
     * @return $this
     * @throws CallableNotFoundException
     */
	public function import($method)
	{
	    if (!$method instanceof \ReflectionMethod) {
            try {
                $method = new \ReflectionMethod($this->class->name, $method);
            } catch (\Throwable $e) {
                throw Error::methodNotFound($this->class->name . "::" . $method);
            }
        }
		$this->name   = $method->name;
		$this->method = $method->class . "::" . $method->name;
		$this->_importFromReflection($method);

		return $this;
	}

	public function getClass() : ClassInfo
    {
        return $this->class;
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
			$filter = \Koda::getFilter($this->method);
		}
		$args = $this->filterArgs($params, $filter);
        try {
            if ($filter->hasContext()) {
                return $filter->getContext()->{$this->name}(...$args);
            } else {
                return $this->getClassName()::{$this->name}(...$args);
            }
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