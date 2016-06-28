<?php

namespace Koda;

class ClassInfo
{
	const FLAG_OBJECT = 1;
	const FLAG_STATIC = 2;
	const FLAG_BOTH   = 3;

	/**
	 * @var MethodInfo[]
	 */
	public $methods = [];
	/**
	 * @var array
	 */
	public $options = [];
	/**
	 * @var string
	 */
	public $name;
	public $desc = "";

	/**
	 * @param string $class_name
	 * @param int $load       load static or object methods or both
	 * @param string $pattern method name mask (glob syntax)
	 */
	public function __construct($class_name, $load = self::FLAG_BOTH, $pattern = "*")
	{
		$this->name = $class_name;
		$this->ref  = new \ReflectionClass($this->name);

		if ($load) {
			foreach ($this->ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $me) {
				if ($load === self::FLAG_OBJECT) {
					if ($me->isStatic()) {
						continue;
					}
				} elseif ($load === self::FLAG_STATIC) {
					if (!$me->isStatic()) {
						continue;
					}
				}
				if ($pattern !== "*" && !fnmatch($pattern, $me->name)) {
					continue;
				}
				$method = new MethodInfo();
				$method->import($me);
				$this->methods[$me->name] = $method;
			}
		}
	}

	public function __toString()
	{
		return $this->name;
	}

	/**
	 * Get method info from class
	 *
	 * @param string $method
	 *
	 * @return MethodInfo|bool
	 */
	public function getMethod($method)
	{
		if (isset($this->methods[$method])) {
			return $this->methods[$method];
		} elseif (method_exists($this->name, $method)) {
			return $this->methods[$method] = MethodInfo::scan($this->name, $method);
		} else {
			return false;
		}
	}


	public function instance(array $params, Filter $filter = null)
	{

	}
} 