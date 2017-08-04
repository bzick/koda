<?php

use Koda\ClassInfo;
use Koda\FunctionInfo;
use Koda\MethodInfo;

class Koda
{

    /**
     *
     * @param string $class_name
     *
     * @return ClassInfo
     */
    public static function getClassInfo(string $class_name) : ClassInfo
    {
        return new ClassInfo($class_name);
    }

    /**
     * @param $cb
     *
     * @return FunctionInfo|MethodInfo
     */
    public static function getMethodInfo($cb)
    {
        if (is_array($cb)) {
            return Koda\MethodInfo::scan($cb[0], $cb[1]);
        } elseif (is_object($cb)) {
            if ($cb instanceof Closure) {
                return FunctionInfo::scan($cb);
            } else {
                return MethodInfo::scan($cb, "__invoke");
            }
        } else {
            if (strpos($cb, '::')) {
                $cb = explode('::', $cb, 2);

                return MethodInfo::scan($cb[0], $cb[1]);
            } else {
                return FunctionInfo::scan($cb);
            }
        }
    }

    public static function getFilter($cb, $class_name = \Koda\Filter::class)
    {
        if (is_object($cb)) {
            if ($cb instanceof Closure) {
                return new \Koda\Filter(null);
            } else {
                return new \Koda\Filter($cb);
            }
        } else {
            if (is_array($cb) && is_object($cb[0])) {
                return new $class_name($cb[0]);
            } else {
                return new $class_name(null);
            }
        }
    }


    /**
     * @param callable $cb
     * @param array $args
     * @param array $options
     *
     * @return mixed
     */
    public static function call($cb, array $args = [], array $options = [])
    {
        $info   = self::getMethodInfo($cb);
        $filter = self::getFilter($cb, isset($options['context']) ? $options['context'] : \Koda\Filter::class);
        if (isset($options["factory"])) {
            $filter->setFactory($options["factory"]);
        }
        if (isset($options["injector"])) {
            $filter->setInjector($options["injector"]);
        }

        return $info->invoke($args, $filter);
    }

    /**
     * @param $class_name
     * @param array $args
     * @param array $options
     *
     * @return mixed
     * @throws \Koda\Error\CallableNotFoundException
     * @throws \Koda\Error\InvalidArgumentException
     */
    public static function object($class_name, array $args = [], array $options = [])
    {
        $filter = self::getFilter([$class_name, "__construct"],
            isset($options['context']) ? $options['context'] : \Koda\Filter::class);
        if (isset($options["factory"])) {
            $filter->setFactory($options["factory"]);
        }
        if (isset($options["injector"])) {
            $filter->setInjector($options["injector"]);
        }
        return (new ClassInfo($class_name))->instance($args, $filter);
    }
}