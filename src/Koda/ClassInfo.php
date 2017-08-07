<?php

namespace Koda;

use Koda\Error\BaseException;
use Koda\Error\InvalidArgumentException;

class ClassInfo implements \JsonSerializable
{
    use OptionsTrait;

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
	public $class;
	public $desc = "";

    public static function scan(string $name, array $options = [], array $filters = []) : ClassInfo {
        try {
            $ce = new \ReflectionClass($name);
        } catch (\Exception $e) {
            throw Error::classNotFound($name);
        }
        $class = new static($name);
        $class->import($ce);
        if (isset($options["method"])) {
            $flags = self::decodeFlags($options["method"], \ReflectionMethod::IS_FINAL | \ReflectionMethod::IS_ABSTRACT);
            if (($options["method"] & self::FLAG_CONSTRUCT) && method_exists($name, "__construct")) {
                $class->addMethod(MethodInfo::scan($name, "__construct"));
            }
            foreach ($ce->getMethods($flags) as $me) {
                 if ($me->isStatic()) {
                    if (!($options["property"] & self::FLAG_STATIC)) {
                        continue;
                    }
                } else {
                    if (!($options["property"] & self::FLAG_NON_STATIC)) {
                        continue;
                    }
                }
                if ($me->class !== $name && !($options["method"] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }

                $mi = new MethodInfo($class);
                $mi->import($me);
                $class->addMethod($mi);
            }
        }

        if (isset($options["property"])) {
            $flags = self::decodeFlags($options["property"], 0);
            foreach ($ce->getProperties($flags) as $prop) {
                if ($prop->isStatic()) {
                    if (!($options["property"] & self::FLAG_STATIC)) {
                        continue;
                    }
                } else {
                    if (!($options["property"] & self::FLAG_NON_STATIC)) {
                        continue;
                    }
                }
                if ($prop->class !== $name && !($options["property"] & self::FLAG_INHERITED)) {
                    continue; // skip methods from another classes if option do not have a flag FLAG_INHERITED
                }

                $pi = new PropertyInfo($class);
                $pi->import($prop, $ce);
                $class->addProperty($pi);
            }

            if ($options["property"] & self::FLAG_DOCBLOCK && $class->hasOption("property")) {
                foreach ($class->getOptions("property") as $val) {
                    $pi = new PropertyInfo($class);
                    $pi->parseHint($val, $class, false);
                    $class->addProperty($pi);
                }
            }
        }

        return $class;
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
		if (isset($this->methods[$method])) {
			return $this->methods[$method];
		} elseif (method_exists($this->name, $method) && $autoscan) {
			return $this->methods[$method] = MethodInfo::scan($this->name, $method);
		} else {
			return null;
		}
	}

    /**
     * @param MethodInfo $method
     */
	public function addMethod(MethodInfo $method)
    {
        $this->methods[$method->name] = $method;
    }

    /**
     * @param string $method
     *
     * @return bool
     */
    public function hasMethod(string $method) : bool {
        return isset($this->methods[$method]);
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