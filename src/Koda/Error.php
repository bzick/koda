<?php

namespace Koda;

use Koda\Error\CallableNotFoundException;
use Koda\Error\CallException;
use Koda\Error\ClassNotFound;
use Koda\Error\CreateException;
use Koda\Error\InvalidArgumentException;
use Koda\Error\TypeCastingException;

class Error
{
    // pseudo filter types
    const FILTER_REQUIRED = 'required';
    const FILTER_CAST     = 'cast';
    const FILTER_INJECT   = 'inject';
    const FILTER_FACTORY  = 'factory';

    /**
     * @param CallableInfoAbstract $callable_info
     * @param \Throwable $error
     *
     * @return CallException
     */
    public static function methodCallFailed(CallableInfoAbstract $callable_info, \Throwable $error = null)
    {
        $ex           = new CallException("Failed to call method $callable_info", 0, $error);
        $ex->callable = $callable_info;

        return $ex;
    }

    /**
     * @param ClassInfo $class
     * @param MethodInfo $constructor
     * @param \Throwable $error
     *
     * @return CreateException
     */
    public static function objectCreateFailed(ClassInfo $class, MethodInfo $constructor, \Throwable $error) {
        $ex           = new CreateException("Failed to create instance {$class->name}", 0, $error);
        $ex->callable = $constructor;
        $ex->class    = $class;

        return $ex;
    }

    /**
     * @param string $method
     *
     * @return CallableNotFoundException
     */
    public static function methodNotFound($method)
    {
        return new CallableNotFoundException("Method {$method} not found");
    }

    /**
     * @param string $method
     *
     * @return CallableNotFoundException
     */
    public static function functionNotFound($method)
    {
        return new CallableNotFoundException("Function {$method} not found");
    }

    /**
     * @param string $class
     *
     * @return ClassNotFound
     */
    public static function classNotFound($class)
    {
        return new ClassNotFound("Class {$class} not found");
    }

    /**
     * @param ArgumentInfo $arg
     * @param string $filter
     * @param \Throwable $error
     *
     * @return InvalidArgumentException
     */
    public static function filteringFailed(ArgumentInfo $arg, $filter, \Throwable $error = null)
    {
        if ($error) {
            $ex = new InvalidArgumentException("Error occurred while filtering of the argument $arg: {$error->getMessage()}",
                0, $error);
        } else {
            $ex = new InvalidArgumentException("Argument $arg has invalid value. Require " . $arg->filters[$filter]['original']);
        }
        $ex->argument = $arg;
        $ex->filter   = $filter;

        return $ex;
    }

    /**
     * @param ArgumentInfo $arg
     *
     * @return InvalidArgumentException
     */
    public static function argumentRequired(ArgumentInfo $arg)
    {
        $ex           = new InvalidArgumentException("Required argument $arg");
        $ex->argument = $arg;
        $ex->filter   = self::FILTER_REQUIRED;

        return $ex;
    }

    /**
     * @param ArgumentInfo $arg
     * @param mixed $value
     * @param mixed $index
     *
     * @return TypeCastingException
     */
    public static function invalidType(ArgumentInfo $arg, &$value, $index)
    {
        $from_type = gettype($value);
        if ($arg->type == "object") {
            $message = "Argument " . $arg->getName($index) . " should be "
                . ($arg->multiple ? "an array of objects {$arg->class_hint}" : "an object {$arg->class_hint}");
        } else {
           $message =  "Argument " . $arg->getName($index) . " should be "
               . ($arg->multiple ? "an array of {$arg->type}s" : "{$arg->type}");
        }
        if ($from_type == "object") {
            $message .= ", $from_type ".get_class($value)." given";
        } else {
            $message .= ", $from_type given";
        }
        $ex = new TypeCastingException($message);
        $ex->argument  = $arg;
        $ex->filter    = self::FILTER_CAST;
        $ex->from_type = $from_type;

        return $ex;
    }

    /**
     * @param ArgumentInfo $arg
     * @param \Throwable $error
     *
     * @return InvalidArgumentException
     */
    public static function injectionFailed(ArgumentInfo $arg, \Throwable $error)
    {
        $ex           = new InvalidArgumentException(
            "Injection of object {$arg->inject} failed into $arg: {$error->getMessage()}",
            0, $error
        );
        $ex->argument = $arg;
        $ex->filter   = self::FILTER_INJECT;

        return $ex;
    }

    /**
     * @param ArgumentInfo $arg
     * @param \Exception $error
     *
     * @return InvalidArgumentException
     */
    public static function factoryFailed(ArgumentInfo $arg, \Exception $error)
    {
        $ex           = new InvalidArgumentException(
            "Object creation of {$arg->class_hint} failed for $arg: {$error->getMessage()}",
            0, $error
        );
        $ex->argument = $arg;
        $ex->filter   = self::FILTER_INJECT;

        return $ex;
    }
}