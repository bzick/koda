<?php

namespace Koda\Error;

use Koda\FunctionInfo;

class CallException extends \Exception
{

    /**
     * @var FunctionInfo
     */
    public $callable;

}