<?php

abstract class ControlBase
{
	protected $name;
	protected $format;
	protected $location;
	protected $classes = array();

	protected $useLocation = false;
	protected $settings = array();
	protected $autoSettings = array();

	public function __construct($format, $location = null, $settings = array())
	{
		$this->format = $format;
		$this->location = $location;
		if(is_array($settings)) {
			$this->settings = $settings;
		}
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

			$input = $form->createInput('location')->
				setLabel('Location');

			if(isset($this->location)) {
				$path = Location::getPathById($this->location);
				if($path) {
					$input->setValue($path);
				}
			}

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

	public function modifyForm($form)
	{
		$form->changeSection('settings')->
			setLegend('Settings');

		foreach($this->autoSettings as $label => $name) {
			$input = $form->createInput($name)->
				setLabel($label);

			if(isset($this->settings[$name])) {
				$input->setValue($this->settings[$name]);
			}
		}

		if(count($this->autoSettings) > 0) {
			return $form;
		} else {
			return false;
		}
	}

	public function processLocalSettings($input)
	{
		$input = Input::getInput();

		foreach($this->autoSettings as $name) {
			if(isset($input[$name])) {
				$this->settings[$name] = $input[$name];
			}
		}

		return true;
	}

	abstract public function getContent();

}

?>