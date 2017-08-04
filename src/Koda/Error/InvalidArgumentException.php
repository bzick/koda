<?php

namespace Koda\Error;

class InvalidArgumentException extends BaseException
{
    // pseudo types
    const FILTER_REQUIRED = 'required';
    const FILTER_CAST     = 'cast';

    public $argument;
    public $filter;
}