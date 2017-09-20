<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

abstract class CallableInfoAbstract  implements \JsonSerializable
{
    use OptionsTrait;

	public $namespace;
	public $name;
	public $desc = "";
    /**
     * @var ClassInfo
     */
	public $class;
    /**
     * @var object
     */
	protected $_ctx;
	/**
	 * @var ArgumentInfo[]
	 */
	public $args = [];
	/**
	 * @var ReturnInfo
	 */
	public $return;
	public $params  = [];

	abstract public function import($callable);

	public function getDescription()
	{
		return $this->desc;
	}

	public function hasArguments() : bool
	{
		return (bool)$this->args;
	}

	public function getArguments() : array
	{
		return $this->args;
	}

	public function hasArgument(string $name) : bool
    {
	    return isset($this->args[$name]);
    }

	public function setArgument(ArgumentInfo $arg) : self
    {
        $this->args[$arg->name] = $arg;
        return $this;
    }

    public function getArgument($name)
    {
        if ($this->hasArgument($name)) {
            return $this->args[$name];
        } else {
            throw new \InvalidArgumentException("Argument $name not found in $this");
        }
    }

    public function setReturn(ReturnInfo $return) : self
    {
        $this->return = $return;
        return $this;
    }

    public function hasReturn() : bool
    {
        return boolval($this->return);
    }

	public function getReturn() : ReturnInfo
	{
		return $this->return;
	}

	public function __toString()
	{
		return $this->name;
	}

	public function setName(string $name) : self
    {
        $this->name = $name;
        return $this;
    }

	public function hasClass() : bool {
	    return (bool)$this->class;
    }

    public function getClassName() : string
    {
	    return $this->class->name ?? '';
    }

    public function getClass() : ClassInfo
    {
	    return $this->class;
    }

    public function hasContext() : bool
    {
        return $this->_ctx || ($this->hasClass() && $this->getClass()->hasContext());
    }

    public function getContext()
    {
	    if ($this->_ctx) {
            return $this->_ctx;
        } elseif  ($this->hasClass() && $this->getClass()->hasContext()) {
	        return $this->getClass()->getContext();
        }
        return null;
    }

    public function setContext($ctx) {
        $this->_ctx = $ctx;
        return $this;
    }

	/**
	 * @param array $params
	 * @param Handler $filter
	 *
	 * @return array
	 * @throws \Koda\Error\TypeCastingException
	 * @throws InvalidArgumentException
	 */
	public function filterArgs(array $params, Handler $filter)
	{
		$args = [];
        foreach ($this->args as $name => $arg) {
            if ($arg->inject) {
                $params[$name] = $filter->injection($arg, isset($params[$name]) ? $params[$name] : null);
            }
            if ($arg->variadic) {
                foreach ($params as $param) {
                    $args[] = $param;
                }
                break;
            }
            if (isset($params[$name])) {
                $param  = $params[$name];
                $args[] = $arg->filter($param, $filter);
                unset($params[$name]);
            } elseif (isset($params[$arg->position])) {
                $param  = $params[$arg->position];
                $args[] = $arg->filter($param, $filter);
                unset($params[$arg->position]);
            } elseif ($arg->optional) {
                $args[] = $arg->default;
                continue;
            } else {
                throw Error::argumentRequired($arg);
            }
        }

		return $args;
	}

	/**
	 * Invoke callback
	 *
	 * @param array $params
	 * @param Handler $filter
	 *
	 * @return mixed
	 * @throws \Koda\Error\TypeCastingException
	 */
	abstract public function invoke(array $params, Handler $filter);
	abstract public function __debugInfo();

	/**
	 * Import callable info from reflection
	 *
	 * @param \ReflectionFunctionAbstract $method
	 *
	 * @return static
	 */
	protected function _importFromReflection(\ReflectionFunctionAbstract $method)
	{
		$doc          = $method->getDocComment();
		$this->return = new ReturnInfo($this);
		if ($doc) {
		    $options = ParseKit::parseDocBlock($doc);
		    foreach ($options as $name => $values) {
                switch ($name) {
                    case 'desc':
                        if (empty($this->desc)) {
                            $this->desc = implode("\n", $values);
                        }
                        break;
                    case 'param':
                        foreach ($values as $val) {
                            if (preg_match('/\$(\w+)/mS', $val, $matches)) {
                                $this->params[$matches[1]] = $val;
                            }
                        }
                        break;
                    case 'return':
                        $return = preg_split('~\s+~mS', $values[0], 2);
                        if ($return) {
                            $this->return->type = $return[0];
                            if (count($return) == 2) {
                                $this->return->desc = $return[1];
                            }
                        }
                        break;
                    default:
                        $this->options[$name] = $values;
                }
            }
		}
		foreach ($method->getParameters() as $param) {
			$this->args[$param->name] = $arg = new ArgumentInfo($this);
			$arg->import($param);
		}

		$this->return = new ReturnInfo($this);
		$this->return->import($method->getReturnType());

		return $this;
	}

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        return $this->__debugInfo();
    }
}