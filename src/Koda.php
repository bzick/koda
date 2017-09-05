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
     * @return \Koda\CallableInfoAbstract
     */
    public static function getMethodInfo($cb)
    {
        if (is_array($cb)) {
            list($class, $cb) = $cb;
            $callable = new MethodInfo(new ClassInfo($class));
        } elseif (is_object($cb)) {
            if ($cb instanceof Closure) {
                $callable = new FunctionInfo();
            } else {
                $callable = new MethodInfo($cb);
            }
        } else {
            if (strpos($cb, '::')) {
                list($class, $cb) = explode('::', $cb, 2);
                $callable = new MethodInfo(new ClassInfo($class));
            } else {
                $callable = new FunctionInfo();
            }
        }
        $callable->import($cb);
        return $callable;
    }

    public static function getFilter($cb, $class_name = \Koda\Handler::class)
    {
        $handler = new $class_name();
        /** @var \Koda\Handler $handler */
        if (is_object($cb)) {
            if ($cb instanceof Closure) {
                return $handler;
            } else {
                return $handler->setContext($cb);
            }
        } else {
            if (is_array($cb) && is_object($cb[0])) {
                return $handler->setContext($cb[0]);
            } else {
                return $handler;
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
        $filter = self::getFilter($cb, isset($options['context']) ? $options['context'] : \Koda\Handler::class);
        if (isset($options["factory"])) {
            $filter->setFactory($options["factory"]);
        }
        if (isset($options["injector"])) {
            $filter->setInjector($options["injector"]);
        }

        return $info->invoke($args, $filter);
    }

    /**
     * @param string $class_name
     * @param array $args
     * @param array $options
     *
     * @return mixed
     * @throws \Koda\Error\CallableNotFoundException
     * @throws \Koda\Error\InvalidArgumentException
     */
    public static function object(string $class_name, array $args = [], array $options = [])
    {
        $filter = self::getFilter([$class_name, "__construct"], $options['context'] ?? \Koda\Handler::class);
        if (isset($options["factory"])) {
            $filter->setFactory($options["factory"]);
        }
        if (isset($options["injector"])) {
            $filter->setInjector($options["injector"]);
        }
        return (new ClassInfo($class_name))->createInstance($args, $filter);
    }
}