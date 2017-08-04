<?php

namespace Koda;

class ClassInfo implements \JsonSerializable
{
	const FLAG_NON_STATIC = 1;
	const FLAG_STATIC     = 2;
	const FLAG_INHERITED  = 4;
	const FLAG_PUBLIC     = 8;
	const FLAG_PROTECTED  = 16;
	const FLAG_PRIVATE    = 32;

	const FLAG_ALL   = self::FLAG_NON_STATIC | self::FLAG_STATIC    | self::FLAG_INHERITED |
                       self::FLAG_PUBLIC     | self::FLAG_PROTECTED | self::FLAG_PRIVATE;

	/**
	 * @var MethodInfo[]
	 */
	public $methods  = [];
	public $constant = [];
	public $property = [];
	/**
	 * @var array
	 */
	public $options = [];
	/**
	 * @var string
	 */
	public $name;
	public $namespace;
	public $class;
	public $desc = "";

    public static function scan(string $name, array $options = [], array $filters = []) : ClassInfo {
        try {
            $ce = new \ReflectionClass($name);
        } catch (\Exception $e) {
            throw Error::classNotFound($name);
        }
        $info = new static($name);
        if (isset($options["method"])) {
            $flags = self::decodeFlags($options["methods"], \ReflectionMethod::IS_FINAL | \ReflectionMethod::IS_ABSTRACT);
            foreach ($ce->getMethods($flags) as $me) {
                if (!$me->isStatic() && !($options["methods"] & self::FLAG_NON_STATIC)) {
                    continue; // skip none static methods if option do not have a flag FLAG_NON_STATIC
                }
                if ($me->class !== $name && !($options["methods"] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }

                $mi = new MethodInfo();
                $mi->import($me);
                $info->addMethod($mi);
            }
        }

        if (isset($options["property"])) {
            $flags = self::decodeFlags($options["property"], 0);
            foreach ($ce->getProperties($flags) as $prop) {
                if (!$prop->isStatic() && !($options["property"] & self::FLAG_NON_STATIC)) {
                    continue; // skip none static methods if option do not have a flag FLAG_NON_STATIC
                }
                if ($prop->class !== $name && !($options["property"] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }

                $pi = new PropertyInfo($info);
                $pi->import($prop);
                $info->addProperty($pi);
            }
        }




        return $info;
    }

    private static function decodeFlags(int $flags, int $start = 0) : int {
        $start |= ($flags & self::FLAG_PUBLIC)    ? \ReflectionMethod::IS_PUBLIC    : 0;
        $start |= ($flags & self::FLAG_PROTECTED) ? \ReflectionMethod::IS_PROTECTED : 0;
        $start |= ($flags & self::FLAG_PRIVATE)   ? \ReflectionMethod::IS_PRIVATE   : 0;
        $start |= ($flags & self::FLAG_STATIC)    ? \ReflectionMethod::IS_STATIC    : 0;

        return $start;
    }

	/**
	 * @param string $class_name
	 */
	public function __construct($class_name)
	{
        $this->name = $class_name;
	}

	public function __toString()
	{
		return $this->name;
	}

    /**
     * Get method info from class
     *
     * @param string $method
     * @param bool $autoscan
     *
     * @return bool|MethodInfo
     */
	public function getMethod(string $method, bool $autoscan = false)
	{
		if (isset($this->methods[$method])) {
			return $this->methods[$method];
		} elseif (method_exists($this->name, $method) && $autoscan) {
			return $this->methods[$method] = MethodInfo::scan($this->name, $method);
		} else {
			return false;
		}
	}

	public function addMethod(MethodInfo $method)
    {
        $this->methods[$method->name] = $method;
    }

    public function addProperty(PropertyInfo $prop)
    {
        $this->methods[$prop->name] = $prop;
    }

	public function getProperty(string $property)
    {
        return $this->property[$property] ?? null;
    }

	public function instance(array $params, Filter $filter = null)
	{

	}


	public function __debugInfo() {
	    return [
            "class" => $this->class,
            "methods" => $this->class,
        ];
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