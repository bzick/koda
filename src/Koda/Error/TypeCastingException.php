<?php

namespace Koda\Error;

class TypeCastingException extends InvalidArgumentException {
	/**
	 * @var string
	 */
	public $from_type;
}