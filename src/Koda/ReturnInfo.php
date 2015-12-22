<?php

namespace Koda;


class ReturnInfo {

	public $cb;
	public $type = false;
	public $desc = "";
	public $class;

	public function __construct(CallableInfo $cb) {
		$this->cb = $cb;
	}

	public function hasType() {
		return (bool)$this->type;
	}

	public function getType() {
		return $this->type;
	}

	public function getClass() {
		return $this->class;
	}

	public function getDescription() {
		return $this->desc;
	}
}