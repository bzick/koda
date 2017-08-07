<?php

namespace Koda;


trait OptionsTrait
{

    /**
     * @var array[] any options
     */
    public $options = [];

    /**
     * @param string $option
     *
     * @return bool
     */
    public function hasOption(string $option) : bool
	{
		return !empty($this->options[$option]);
	}

    /**
     * @param string $option
     * @param mixed $index
     *
     * @return mixed
     */
	public function getOption(string $option, $index = 0)
	{
		return !empty($this->options[$option][$index]) ? $this->options[$option][$index] : null;
	}

    /**
     * @param string $option
     *
     * @return array
     */
	public function getOptions(string $option) : array
	{
		return isset($this->options[$option]) ? $this->options[$option] : [];
	}

}