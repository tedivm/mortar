<?php

class MortarActionControlSettings extends FormAction
{
	static $requiredPermission = 'System';

	public $adminSettings = array( 'headerTitle' => 'Control Settings', 'useRider' => true);

	protected $control;

	public function logic()
	{
		$query = Query::getQuery();

		$user = ActiveUser::getUser();
		$cs = new ControlSet($user->getId());
		$cs->loadControls();
		$info = $cs->getInfo();

		$url = new Url();
		$url->module = 'Mortar';
		$url->action = 'Dashboard';
		$url->format = 'admin';

		if(isset($query['id']) && isset($info[$query['id']])) {
			$this->control = $cs->getControl($query['id']);
		} else {
			$url = new Url();
			$url->module = 'Mortar';
			$url->action = 'Dashboard';
			$url->format = 'admin';
			$this->ioHandler->addHeader('Location', (string) $url);
		}

/*		$this->form = $this->getForm();

		if($this->form->checkSubmit())
		{
			$this->processInput($this->form->getInputHandler());
			$this->ioHandler->addHeader('Location', (string) $url);
		}*/
	}

	public function viewAdmin($page)
	{

	}

	protected function processInput($input)
	{
		return true;
	}

	protected function getForm()
	{
		return true;
	}
}

?>