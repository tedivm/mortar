<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package Mortar
 * @subpackage Dashboard
 */

/**
 * The ControlSettings action generates a Form for modifying a given Control's settings.
 *
 * @package Mortar
 * @subpackage Dashboard
 */
class MortarActionControlSettings extends FormAction
{
	static $requiredPermission = 'System';

	public static $settings = array( 'Base' => array( 'headerTitle' => 'Control Settings', 'useRider' => true) );

	protected $cs;
	protected $control;

	/**
	 * Loads the ControlSet and specific Control for the user and id provided, then fetches the form; outputs
	 * the form if it hasn't been submitted, or saves the changes then redirects back to the Dashboard otherwise.
	 *
	 */
	public function logic()
	{
		$query = Query::getQuery();

		$user = ActiveUser::getUser();
		$this->cs = new ControlSet($user->getId());
		$this->cs->loadControls();
		$info = $this->cs->getInfo();

		$url = new Url();
		$url->module = 'Mortar';
		$url->action = 'Dashboard';
		$url->format = $query['format'];

		if(isset($query['id']) && isset($info[$query['id']])) {
			$this->control = $this->cs->getControl($query['id']);
		} else {
			$this->ioHandler->addHeader('Location', (string) $url);
		}

		$this->setSetting('titleRider', 'Base', ' For ' . $this->control->getName());

		$this->form = $this->getForm();

		if($this->form !== false && $this->form->checkSubmit())
		{
			$this->processInput($this->form->getInputHandler());
			$this->cs->saveControls();
			$this->ioHandler->addHeader('Location', (string) $url);
		}
	}

	/**
	 * Just sends back an error message if there are no manual settings, or the form otherwise.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		if($this->form === false) {
			$output = 'This control has no manual settings.';
		} elseif ($this->form->wasSubmitted()) {
                	$output = '';
		} else {
	                $output = $this->form->getFormAs('Html');
	        }
                return $output;
	}

	public function viewHtml($page)
	{
		return $this->viewAdmin($page);
	}

	/**
	 * Our input processing is completely passed off to the Control; we just call that class' processSettingsInput
	 * method here.
	 *
	 * @return bool
	 */
	protected function processInput($input)
	{
		return $this->control->processSettingsInput($input);
	}

	/**
	 * We generate an empty form with the correct name and legend, then pass it onto the control itself
	 * where it is actually populated.
	 *
	 * @return Form
	 */
	protected function getForm()
	{
		$form = new Form('control_settings');
		$form->setLegend('Control Settings For ' . $this->control->getName());

		$form = $this->control->settingsForm($form);
		return $form;
	}
}

?>