<?php

namespace Koda;

use Koda\Error\BaseException;
use Koda\Error\InvalidArgumentException;

class ClassInfo implements \JsonSerializable
{
    use OptionsTrait;

    const METHODS = "method";
    const PROPS   = "property";

	const FLAG_NON_STATIC = 1;
	const FLAG_STATIC     = 2;
	const FLAG_INHERITED  = 4;
	const FLAG_PUBLIC     = 8;
	const FLAG_PROTECTED  = 16;
	const FLAG_PRIVATE    = 32;

	const FLAG_DOCBLOCK   = 64;
	const FLAG_CONSTRUCT  = 128;

	const FLAG_ALL   = self::FLAG_NON_STATIC | self::FLAG_STATIC    | self::FLAG_INHERITED |
                       self::FLAG_PUBLIC     | self::FLAG_PROTECTED | self::FLAG_PRIVATE;

	/**
	 * @var MethodInfo[]
	 */
	public $methods    = [];
	public $constant   = [];
	public $properties = [];
    /**
     * @var string Parent class
     */
	public $parent = "";
	/**
	 * @var string
	 */
	public $name;
	public $namespace;
	public $desc = "";


    /**
     * @param string $name
     * @param mixed $filters
     *
     * @return bool
     */
    private static function filterNames(string $name, $filters) : bool
    {
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                if (fnmatch($filter, $name)) {
                    return true;
                }
            }
            return false;
        } else {
            return fnmatch($filters, $name);
        }
    }

    private static function decodeFlags(int $flags, int $start = 0) : int {
        $start |= ($flags & self::FLAG_PUBLIC)    ? \ReflectionMethod::IS_PUBLIC    : 0;
        $start |= ($flags & self::FLAG_PROTECTED) ? \ReflectionMethod::IS_PROTECTED : 0;
        $start |= ($flags & self::FLAG_PRIVATE)   ? \ReflectionMethod::IS_PRIVATE   : 0;
        $start |= ($flags & self::FLAG_STATIC)    ? \ReflectionMethod::IS_STATIC    : 0;

        return $start;
    }

	/**
	 * @param string|object $class
	 */
	public function __construct($class)
	{
	    if (is_object($class)) {
	        $this->name = get_class($class);
        } else if(is_string($class)) {
            $this->name = $class;
        } else {
	        throw new \InvalidArgumentException("Given $class is not class name or object");
        }
	}

    public function scan(array $options = [], array $filters = []) : ClassInfo {
        try {
            $ce = new \ReflectionClass($this->name);
        } catch (\Exception $e) {
            throw Error::classNotFound($this->name);
        }
        $this->import($ce);
        if (isset($options[self::METHODS])) {
            $flags = self::decodeFlags($options[self::METHODS], \ReflectionMethod::IS_FINAL | \ReflectionMethod::IS_ABSTRACT);
            if (($options[self::METHODS] & self::FLAG_CONSTRUCT) && method_exists($this->name, "__construct")) {
                $this->addMethod((new MethodInfo($this))->import("__construct"));
            }
            foreach ($ce->getMethods($flags) as $me) {
                if ($me->isStatic()) {
                    if (!($options[self::METHODS] & self::FLAG_STATIC)) {
                        continue;
                    }
                } else {
                    if (!($options[self::METHODS] & self::FLAG_NON_STATIC)) {
                        continue;
                    }
                }
                if ($me->class !== $this->name && !($options[self::METHODS] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }

                if (isset($filters[self::METHODS]) && !self::filterNames($me->name, $filters[self::METHODS])) {
                    continue;
                }

                $mi = new MethodInfo($this);
                $mi->import($me);
                $this->addMethod($mi);
            }
        }

        if (isset($options[self::PROPS])) {
            $flags = self::decodeFlags($options[self::PROPS], 0);
            foreach ($ce->getProperties($flags) as $prop) {
                if ($prop->isStatic()) {
                    if (!($options[self::PROPS] & self::FLAG_STATIC)) {
                        continue;
                    }
                } else {
                    if (!($options[self::PROPS] & self::FLAG_NON_STATIC)) {
                        continue;
                    }
                }
                if ($prop->class !== $this->name && !($options[self::PROPS] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }
                if (isset($filters[self::PROPS]) && !self::filterNames($prop->name, $filters[self::PROPS])) {
                    continue;
                }

                $pi = new PropertyInfo($this);
                $pi->import($prop, $ce);
                $this->addProperty($pi);
            }

            if ($options[self::PROPS] & self::FLAG_DOCBLOCK && $this->hasOption(self::PROPS)) {
                foreach ($this->getOptions(self::PROPS) as $val) {
                    $pi = new PropertyInfo($this);
                    $pi->parseHint($val, $this, false);
                    $this->addProperty($pi);
                }
            }
        }

        return $this;
    }

    /**
     * @param \ReflectionClass|null $class
     *
     * @return $this
     */
	public function import(\ReflectionClass $class = null) {
	    if(!$class) {
	        $class = new \ReflectionClass($this->name);
        }
        if ($pc = $class->getParentClass()) {
	        $this->parent = $pc->name;
        }
	    if ($doc = $class->getDocComment()) {
            $this->options = ParseKit::parseDocBlock($doc);
        }

        return $this;
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
	    $me = strtolower($method);
		if (isset($this->methods[$me])) {
			return $this->methods[$me];
		} elseif (method_exists($this->name, $me) && $autoscan) {
		    $m = new MethodInfo($this);
			return $this->methods[$me] = $m->import($method);
		} else {
			return null;
		}
	}

    /**
     * @param MethodInfo $method
     */
	public function addMethod(MethodInfo $method)
    {
        $this->methods[strtolower($method->name)] = $method;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function hasMethod(string $method) : bool {
        return isset($this->methods[strtolower($method)]);
    }

    /**
     * @param PropertyInfo $prop
     */
    public function addProperty(PropertyInfo $prop)
    {
        $this->properties[$prop->name] = $prop;
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
	public function getProperty(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * @param string $property
     *
     * @return bool
     */
    public function hasProperty(string $property) : bool {
	    return isset($this->properties[$property]);
    }

    /**
     * @return bool
     */
    public function hasParentClass() : bool {
        return (bool)$this->parent;
    }

    /**
     * @return string
     */
    public function getParentClassName() : string {
        return $this->parent;
    }

    /**
     * @param array $args
     * @param Handler|null $filter
     *
     * @return mixed
     * @throws BaseException
     * @throws Error\CreateException
     * @throws InvalidArgumentException
     */
	public function createInstance(array $args, Handler $filter = null)
	{
	    $class_name = $this->name;
	    $c = $this->getMethod("__construct", true);
	    try {
            if ($c && $c->hasArguments()) {
                $args = $c->filterArgs($args, $filter);
                return new $class_name(...$args);
            } else {
                return new $class_name();
            }
        } catch (BaseException $error) {
	        throw $error;
        } catch (\TypeError $error) {
            throw new InvalidArgumentException("Some of the arguments were not converted to the correct type", 0, $error);
        } catch (\ArgumentCountError $error) {
	        throw new InvalidArgumentException("Too few arguments are passed", 0, $error);
        } catch (\Throwable $error) {
            throw Error::objectCreateFailed($this, $c, $error);
        }
	}


	public function __debugInfo() {
	    return [
            "class" => $this->name,
            "methods" => $this->methods,
            "properties" => $this->properties,
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