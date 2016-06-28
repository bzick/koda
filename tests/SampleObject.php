<?php

namespace Koda;


class SampleObject
{

    public $index;
    public $inject;
    public $factory;
    /**
     * SampleObject constructor.
     *
     * @param int $index
     * @param int $inject (inject age)
     * @param \ArrayObject $factory
     */
    public function __construct($index, $inject, $factory)
    {
        $this->index = $index;
        $this->inject = $inject;
        $this->factory = $factory;
    }
}