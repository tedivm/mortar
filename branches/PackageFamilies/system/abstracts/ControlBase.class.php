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
 * The ControlBase contains the basic guts for Controls (content widgets for use in the Mortar Dashboard and
 * other locations). All Control classes should extend this abstract, which handles the basics for
 * settings and form/output functionality.
 *
 * @package System
 * @subpackage Dashboard
 */
abstract class ControlBase
{
	/**
	 * The name of the Control. This should be set in advance as it's used by the ControlRegistry when the 
	 * Control is registered.
	 *
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * Whether this control makes use of a location variable. Defaults to false. Set in advance.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $useLocation = false;

	/**
	 * A list of settings which should automatically be created in the settings dialogue for the control.
	 * Uses the format ( 'Label' => 'setting_name' ) Set in advance.
	 *
	 * @access protected
	 * @var array
	 */
	protected $autoSettings = array();

	/**
	 * The set of classes which should be applied to the HTML element of the control when it is registered. Set
	 * in advance.
	 * 
	 * @access protected
	 * @var array
	 */
	protected $classes = array();

	/**
	 * The format the Control is currently being used with. Set at runtime after the control is initialized.
	 *
	 * @access protected
	 * @var string
	 */
	protected $format;

	/**
	 * The location, if any, which this Control acts on or references. Set at runtime.
	 *
	 * @access protected
	 * @var int
	 */
	protected $location;

	/**
	 * An array of current settings for this instance of the Control, in the form ('setting_name' => 'value').
	 *
	 * @access protected
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Constructor takes the format with which this Control is being initialized. Optionally takes
	 * a location and an array of settings to initialize this Control to.
	 *
	 * @param string $format
	 * @param int $location = null
	 * @param array $settings = array()
	 * @return array
	 */
	public function __construct($format, $location = null, $settings = array())
	{
		$this->format = $format;
		$this->location = $location;
		if(is_array($settings)) {
			$this->settings = $settings;
		}
	}

	/**
	 * Returns the list of classes, space-delimited for inclusion in an HTML class declaration.
	 *
	 * @return string
	 */
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

	/**
	 * Returns the name of this control.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns the currently set location, if any.
	 *
	 * @return string
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * Returns the array of current settings.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Sets the current location for this Control.
	 *
	 * @param int $loc
	 */
	public function setLocation($loc)
	{
		$this->location = $loc;
	}

	/**
	 * Sets the settings array for this control.
	 *
	 * @param array $settings
	 * @return bool
	 */
	public function setSettings($settings)
	{
		if(!is_array($settings))
			return false;

		$this->settings = $settings;
		return true;
	}

	/**
	 * Returns a Form object for use by the ControlSettings action, to allow the user to modify the settings for
	 * a given instance of this Control. Creates a Location input if useLocation is true, then passes the form 
	 * through to the modifyForm method, which can be overloaded in order to create custom settings forms.
	 *
	 * @param Form $form
	 * @return Form
	 */
	public function settingsForm($form)
	{
		if($this->useLocation) {
			$form->changeSection('location')->
				setLegend('Location');

			$input = $form->createInput('location')->
				setType('location')->
				setLabel('Location');

			if(isset($this->location)) {
				$input->setValue($this->location);
			}
		}

		$results =  $this->modifyForm($form);
		if ($this->useLocation && !$results) {
			return $form;
		} else {
			return $results;
		}
	}

	/**
	 * Converts the input from the settings form into actual values to be stored in this instance of the Control.
	 * Transforms the location value into an integer, then passes the inputs through to processLocalSettings
	 * which can be overloaded for custom settings forms.
	 *
	 * @param array input
	 * @return bool
	 */
	public function processSettingsInput($input)
	{
		if($this->useLocation) {
			if(isset($input['location']) && is_numeric($input['location']))
				$this->location = $input['location'];
		}

		return $this->processLocalSettings($input);
	}

	/**
	 * Adds control elements for any listed "autoSettings." This method should be overloaded in order to create
	 * custom settings forms.
	 *
	 * @param Form $form
	 * @return Form
	 */
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

	/**
	 * Processes and stores the results of inputs for any "autoSettings." This method should be overloaded in order
	 * to create custom settings forms.
	 *
	 * @param array $input
	 * @return bool
	 */
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

	/**
	 * Returns the ultimate display content for this control. Calls the setName method, adds a header based on
	 * the control's name, then calls the getContent method where the actual Control-specific content is
	 * produced.
	 *
	 * @return string
	 */
	public function display()
	{
		$this->setName();
		$content = '<h3>' . $this->name . '</h3>';
		if ($this->useLocation === false || ($this->useLocation && $this->location)) {
			return $content . $this->getContent();
		} else {
			return $content . "This control requires a location to be set.";
		}
	}

	/**
	 * By default this method does nothing. Should be overloaded by Controls where the display name can vary --
	 * for example, to add a Location name or setting to it.
	 *
	 */
	protected function setName()
	{
	
	}

	/**
	 * This method actually produces the display content
	 *
	 */
	abstract public function getContent();
}

?>