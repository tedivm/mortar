<?php

abstract class ControlBase
{
	protected $name;
	protected $format;
	protected $location;
	protected $classes = array();

	protected $useLocation = false;
	protected $settings = array();

	public function __construct($format, $location = null, $settings = array())
	{
		$this->format = $format;
		$this->location = $location;
		if(is_array($settings)) {
			$this->settings = $settings;
		}
	}

	abstract public function getContent();

	public function getSettingsForm()
	{
		return false;
	}

	public function getClasses()
	{
		$content = '';
		$first = true;

		foreach($this->classes as $class) {
			if($first) {
				$first = false;
			} else {
				$content .= ' ';
			}

			$content .= $class;
		}

		return $content;
	}

	public function getName()
	{
		return $this->name;
	}
}

?>