<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

abstract class CallableInfoAbstract  implements \JsonSerializable
{
    use OptionsTrait;

	public $namespace;
	public $name;
	public $desc = "";
	public $class;
	/**
	 * @var ArgumentInfo[]
	 */
	public $args = [];
	/**
	 * @var ReturnInfo
	 */
	public $return;
	public $params  = [];

	public function getDescription()
	{
		return $this->desc;
	}

	public function hasArguments()
	{
		return (bool)$this->args;
	}

	public function getArguments()
	{
		return $this->args;
	}

	public function getReturn()
	{
		return $this->return;
	}

	public function __toString()
	{
		return $this->name;
	}

	public function hasClass() : bool {
	    return $this->class;
    }

    public function getClassName() : string {
	    return $this->class;
    }

    public function getClass() : ClassInfo {
	    return new ClassInfo($this->class);
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

	public function hasArgument($name) : bool
	{
		return isset($this->args[$name]);
	}

	public function getArgument($name)
	{
		return isset($this->args[$name]) ? $this->args[$name] : false;
	}

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