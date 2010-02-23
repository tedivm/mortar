<?php

abstract class ControlBase
{
	protected $name;
	protected $format;
	protected $location;
	protected $settings = array();

	public function __construct($format, $location = null, $settings = array())
	{
		$this->format = $format;
		$this->location = $location;
		if(is_array($settings)) {
			$this->settings = $settings;
		}
	}
}

?>