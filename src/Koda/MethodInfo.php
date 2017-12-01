<?php

namespace Koda;


use Koda\Error\BaseException;
use Koda\Error\CallableNotFoundException;
use Koda\Error\InvalidArgumentException;

class MethodInfo extends CallableInfoAbstract
{

	public $method = '';
	public $modifiers = 0;

	public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function getMethodName() : string
    {
        return $this->method;
    }

    public function setMethodName(string $name)
    {
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
                $method = new \ReflectionMethod($this->class, $method);
            } catch (\Throwable $e) {
                throw Error::methodNotFound($this->class . "::" . $method);
            }
        }
		$this->name      = $method->name;
		$this->method    = $method->class . "::" . $method->name;
        $this->modifiers = $method->getModifiers();
        $this->_importFromReflection($method);

		return $this;
	}

	/**
	 * Invoke method
	 *
	 * @param array $params
	 * @param ContextHandler $filter
	 *
	 * @return mixed
	 * @throws BaseException
	 */
	public function invoke(array $params, ContextHandler $filter = null)
	{
		if (!$filter) {
			$filter = new ContextHandler();
		}
		if ($this->args) {
            $args = $this->filterArgs($params, $filter);
            return $this->invokeFiltered($args, $filter->getContext());
        } else {
            return $this->invokeFiltered($params, $filter->getContext());
        }
	}

    /**
     * @param array $args
     * @param object $context
     * @return mixed
     * @throws BaseException
     * @throws Error\CallException
     * @throws InvalidArgumentException
     */
	public function invokeFiltered(array $args, $context = null)
    {
        try {
            if ($context) {
                return $context->{$this->name}(...$args);
            } else {
                $class = $this->class;
                return $class::{$this->name}(...$args);
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