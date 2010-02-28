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

	public function getLocation()
	{
		return $this->location;
	}

	public function getSettings()
	{
		return $this->settings;
	}

	public function setLocation($loc)
	{
		$this->location = $loc;
	}

	public function setSettings($settings)
	{
		if(!is_array($settings))
			return false;

		$this->settings = $settings;
	}

	public function settingsForm($form)
	{
		if($this->useLocation) {
			$form->changeSection('location')->
				setLegend('Location');

			$form->createInput('location')->
				setLabel('Location');
		}

		$results =  $this->modifyForm($form);
		if ($this->useLocation && !$results) {
			return $form;
		} else {
			return $results;
		}
	}

	public function processSettingsInput($input)
	{
		if($this->useLocation) {
			$loc = Location::getIdByPath($input['location']);
			if($loc !== false) {
				$this->location = $loc;
			}
		}

		return $this->processLocalSettings($input);
	}

	abstract public function getContent();

	abstract public function modifyForm($form);

	abstract public function processLocalSettings($input);
}

?>