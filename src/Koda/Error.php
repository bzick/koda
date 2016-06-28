<?php

namespace Koda;

use Koda\Error\CallableNotFoundException;
use Koda\Error\CallException;
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
     * @param CallableInfo $callable_info
     * @param \Exception $error
     *
     * @return CallException
     */
    public static function methodCallFailed(CallableInfo $callable_info, \Exception $error = null)
    {
        $ex           = new CallException("Failed to call method $callable_info", 0, $error);
        $ex->callable = $callable_info;

        return $ex;
    }

    /**
     * @param string $method
     *
     * @return CallableNotFoundException
     */
    public static function methodNotFound($method)
    {
        return new CallableNotFoundException("Method not found {$method}");
    }

    /**
     * @param string $method
     *
     * @return CallableNotFoundException
     */
    public static function functionNotFound($method)
    {
        return new CallableNotFoundException("Function not found {$method}");
    }

    /**
     * @param ArgumentInfo $arg
     * @param string $filter
     * @param \Exception|null $error
     *
     * @return InvalidArgumentException
     */
    public static function filteringFailed(ArgumentInfo $arg, $filter, \Exception $error = null)
    {
        if ($error) {
            $ex = new InvalidArgumentException("Error occurred while filtering of the argument $arg: {$error->getMessage()}",
                0, $error);
        } else {
            $ex = new InvalidArgumentException("Argument $arg has invalid value. Require " . $arg->filters[$filter]['original'],
                0, $error);
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
     * @param string $from_type
     *
     * @return TypeCastingException
     */
    public static function invalidType(ArgumentInfo $arg, $from_type)
    {
        if ($arg->type == "object") {
            $ex = new TypeCastingException(
                "Argument $arg should be " .
                ($arg->multiple ? "an array of objects {$arg->class}" : "an object {$arg->class}")
            );
        } else {
            $ex = new TypeCastingException(
                "Argument $arg should be " .
                ($arg->multiple ? "an array of {$arg->type}s" : "{$arg->type}")
            );
        }
        $ex->argument  = $arg;
        $ex->filter    = self::FILTER_CAST;
        $ex->from_type = $from_type;

        return $ex;
    }

    /**
     * @param ArgumentInfo $arg
     * @param \Exception $error
     *
     * @return InvalidArgumentException
     */
    public static function injectionFailed(ArgumentInfo $arg, \Exception $error)
    {
        $ex           = new InvalidArgumentException("Injection of object {$arg->inject} failed into $arg: {$error->getMessage()}",
            0, $error);
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
        $ex           = new InvalidArgumentException("Object creation of {$arg->class} failed for $arg: {$error->getMessage()}",
            0, $error);
        $ex->argument = $arg;
        $ex->filter   = self::FILTER_INJECT;

        return $ex;
    }
}