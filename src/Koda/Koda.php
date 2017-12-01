<?php

namespace Koda;

class Koda
{

    /**
     * @var ContextHandler
     */
    protected $handler;

    public function __construct() {
        $this->handler = new ContextHandler();
    }

    /**
     *
     * @param string $class_name
     *
     * @return ClassInfo
     */
    public function getClassInfo(string $class_name) : ClassInfo
    {
        return new ClassInfo($class_name);
    }

    /**
     * @param mixed $cb
     * @param object $context
     *
     * @return CallableInfoAbstract
     */
    public function getCallableInfo($cb, &$context = null)
    {
        if (is_array($cb)) {
            list($class, $cb) = $cb;
            $callable = new MethodInfo(new ClassInfo($class));
        } elseif (is_object($cb)) {
            if ($cb instanceof \Closure) {
                $callable = new FunctionInfo();
            } else {
                $callable = new MethodInfo($cb);
                $context = $cb;
                $cb = "__invoke";
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

    /**
     * Set context handler.
     * Context handler stores injector and factory callbacks. Also context handler has validators.
     *
     * @param ContextHandler $handler
     */
    public function setContextHandler(ContextHandler $handler)
    {
        $handler->factory = $this->handler->factory;
        $handler->injector = $this->handler->injector;
        $this->handler = $handler;
    }

    public function setFactory(callable $cb)
    {
        $this->handler->factory = $cb;
    }

    public function setInjector(callable $cb)
    {
        $this->handler->injector = $cb;
    }

    /**
     * Returns context handler
     *
     * @return ContextHandler
     */
    public function getContextHandler(): ContextHandler
    {
        return $this->handler;
    }


    /**
     * @param callable $cb
     * @param array $args
     *
     * @return mixed
     */
    public function call($cb, array $args = [])
    {
        $context = null;
        $info = $this->getCallableInfo($cb, $context);

        return $info->invoke($args, $this->handler->setContext($context));
    }

    /**
     * @param CallableInfoAbstract $info
     * @param array $args
     * @param object $context '$this' object if it is method. Ignore argument if it is function.
     *
     * @return mixed
     */
    public function callViaInfo(CallableInfoAbstract $info, array $args = [], $context = null)
    {
        return $info->invoke($args, $this->handler->setContext($context));
    }

    /**
     * @param string $class_name
     * @param array $args
     *
     * @return mixed
     * @throws \Koda\Error\CallableNotFoundException
     * @throws \Koda\Error\InvalidArgumentException
     */
    public function make(string $class_name, array $args = [])
    {
        return (new ClassInfo($class_name))->make($args, $this->handler->setContext(null));
    }
}