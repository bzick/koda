<?php

namespace Koda\Error;

class InvalidArgumentException extends BaseException
{
    public $argument;
    public $filter;
}