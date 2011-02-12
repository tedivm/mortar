<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Dashboard
 */

/**
 * The Add Control adds the ability to register a form used to create a new Model as a Control.
 *
 * @package Mortar
 * @subpackage Dashboard
 */
class MortarCoreControlAdd extends ActionControl
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
			$loc = Location::getLocation($this->location);
			$model = $loc->getResource();

			$name = $model->getDesignation();

			$this->name .= ' At ' . $name;
		}

		return true;
	}

	public function getContent()
	{
		if(!isset($this->settings['type']))
			return 'This control requires a type to be set.';

		$loc = Location::getLocation($this->location);
		$model = $loc->getResource();
		$types = $model->getAllowedChildrenTypes();

		if(!in_array($this->settings['type'], $types)) {
			return "This location doesn't allow for " . $this->settings['type'] . "children.";
		}

		return parent::getContent();
	}
}

?>