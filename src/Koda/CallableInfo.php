<?php

namespace Koda;


use Koda\Error\InvalidArgumentException;

abstract class CallableInfo {

	public $name;
	public $desc = "";
	/**
	 * @var ArgumentInfo[]
	 */
	public $args = array();
	/**
	 * @var ReturnInfo
	 */
	public $return;
	public $options = array();

	public function getDescription() {
		return $this->desc;
	}

	public function hasArguments() {
		return (bool)$this->args;
	}

	public function getArguments() {
		return $this->args;
	}

	public function getReturn() {
		return $this->return;
	}

	public function __toString() {
		return $this->name;
	}

	/**
	 * Import callable info from reflection
	 * @param \ReflectionFunctionAbstract $method
	 * @return static
	 */
	protected function _importFromReflection(\ReflectionFunctionAbstract $method) {
		$doc = $method->getDocComment();
		$doc_params = array();
		$this->return = new ReturnInfo($this);
		if($doc) {
			$doc = preg_replace('/^\s*(\*\s*)+/mS', '', trim($doc, "*/ \t\n\r"));
			if(strpos($doc, "@") !== false) {
				$doc = explode("@", $doc, 2);
				if($doc[0] = trim($doc[0])) {
					$this->desc = $doc[0];
				}
				if($doc[1]) {
					foreach(preg_split('/\r?\n@/mS', $doc[1]) as $param) {
						$param = preg_split('/\s+/S', $param, 2);
						if(!isset($param[1])) {
							$param[1] = "";
						}
						switch(strtolower($param[0])) {
							case 'description':
								if(empty($this->desc)) {
									$this->desc = $param[1];
								}
								break;
							case 'param':
								if(preg_match('/^(.*?)\s+\$(\w+)\s*(?:\(([^\)]+)\))?/mS', $param[1], $matches)) {
									$doc_params[ $matches[2] ] = array(
										"type" => $matches[1],
										"desc" => trim(substr($param[1], strlen($matches[0]))),
										"filters" => isset($matches[3]) ? Filter::parseDoc($matches[3]) : array()
									);
								}
								break;
							case 'return':
								$return = preg_split('~\s+~mS', $param[1], 2);
								if($return) {
									$this->return->type = $return[0];
									if(count($return) == 2) {
										$this->return->desc = $return[1];
									}
								}
								break;
							default:
								if(isset($this->options[ $param[0] ])) {
									$this->options[ $param[0] ][] = $param[1];
								} else {
									$this->options[ $param[0] ] = array( $param[1] );
								}
						}
					}
				}
			} else {
				$this->desc = $doc;
			}

		}
		foreach($method->getParameters() as $param) {
			$this->args[$param->name] = $arg = new ArgumentInfo($this);
			$arg->import($param);
		}
		return $this;
	}

	/**
	 * @param array $params
	 * @param Filter $filter
	 * @return array
	 * @throws \Koda\Error\TypeCastingException
	 * @throws InvalidArgumentException
	 */
	public function filterArgs(array $params, Filter $filter) {
		$args = array();
		foreach ($this->args as $name => $arg) {
			if($arg->inject) {
				$params[ $name ] = $filter->injection($arg, isset($params[ $name ]) ? $params[ $name ] : null);
			}
			if(isset($params[ $name ])) {
				$param = $params[ $name ];
				$args[] = $arg->filter($param, $filter);
				unset($params[ $name ]);
			} elseif(isset($params[ $arg->position ])) {
				$param = $params[ $arg->position ];
				$args[] = $arg->filter($param, $filter);
				unset($params[ $arg->position ]);
			} elseif($arg->optional) {
				$args[] = $arg->default;
				continue;
			} else {
				throw Error::argumentRequired($arg);
			}
		}
		return $args;
	}

	/**
	 * Invoke callback
	 * @param array $params
	 * @param Filter $filter
	 * @return mixed
	 * @throws \Koda\Error\TypeCastingException
	 */
	abstract public function invoke(array $params, Filter $filter);

	public function hasOption($option) {
		return !empty($this->options[$option]);
	}

	public function getOption($option, $index = 0) {
		return !empty($this->options[$option][$index]) ? $this->options[$option][$index] : null;
	}

	public function getOptions($option) {
		return isset($this->options[$option]) ? $this->options[$option] : [];
	}

	public function getArgument($name) {
		return isset($this->args[$name]) ? $this->args[$name] : false;
	}
}