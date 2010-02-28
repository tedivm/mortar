<?php

class MortarActionControlSettings extends FormAction
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Control Settings', 'useRider' => true);

	protected $cs;
	protected $control;

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
		$url->format = 'admin';

		if(isset($query['id']) && isset($info[$query['id']])) {
			$this->control = $this->cs->getControl($query['id']);
		} else {
			$url = new Url();
			$url->module = 'Mortar';
			$url->action = 'Dashboard';
			$url->format = 'admin';
			$this->ioHandler->addHeader('Location', (string) $url);
		}

		$this->form = $this->getForm();

		if($this->form !== false && $this->form->checkSubmit())
		{
			$this->processInput($this->form->getInputHandler());
			$this->ioHandler->addHeader('Location', (string) $url);
		}
	}

	public function viewAdmin($page)
	{
		$this->adminSettings['titleRider'] = ' For ' . $this->control->getName();

		if($this->form === false) {
			$output = 'This control has no manual settings.';
		} elseif ($this->form->wasSubmitted()) {
                	$output = '';
		} else {
	                $output = $this->form->getFormAs('Html');
	        }
                return $output;
	}

	protected function processInput($input)
	{
		return $this->control->processSettingsInput($input);
	}

	protected function getForm()
	{
		$form = new Form('control_settings');
		$form->setLegend('Control Settings For ' . $this->control->getName());

		$form = $this->control->settingsForm($form);
		return $form;
	}
}

?>