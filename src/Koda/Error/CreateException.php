<?php

namespace Koda\Error;

use Koda\ClassInfo;

class CreateException extends CallException
{

    /**
     * @var ClassInfo
     */
    public $class;
}