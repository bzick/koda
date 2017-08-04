<?php

namespace Koda\Error;

use Koda\FunctionInfo;

class CallException extends BaseException
{

    /**
     * @var FunctionInfo
     */
    public $callable;

}