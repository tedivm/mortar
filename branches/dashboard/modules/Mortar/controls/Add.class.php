<?php

class MortarControlAdd extends ActionControl
{
	protected $useLocation = true;

	protected $name = "Add";

	protected $classes = array('two_wide', 'two_tall');

	protected $customQuery = array('action' => 'Add');

	protected $autoSettings = array('Type' => 'type');

	public function __construct($format, $location = null, $settings = null)
	{
		parent::__construct($format, $location, $settings);
		if(isset($this->settings['type']) && $this->settings['type'] != '') {
			$this->customQuery['type'] = $this->settings['type'];
		}
	}

	protected function setName()
	{
		if(isset($this->settings['type']) && $this->settings['type'] !== '') {
			$this->name .= " New " . $this->settings['type'];
		}

		if(isset($this->location)) {
			$loc = new Location($this->location);
			$model = $loc->getResource();

			if(isset($model['title'])) {
				$name = $model['title'];
			} else {
				$name = str_replace('_', ' ', $loc->getName());
			}

			$this->name .= ' At ' . $name;
		}

		return true;
	}

	public function getContent()
	{
		if(!isset($this->settings['type']))
			return 'This control requires a type to be set.';

		$loc = new Location($this->location);
		$model = $loc->getResource();
		$types = $model->getAllowedChildrenTypes();

		if(!in_array($this->settings['type'], $types)) {
			return "This location doesn't allow for " . $this->settings['type'] . "children.";
		}

		return parent::getContent();
	}
}

?>