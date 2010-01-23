<?php

abstract class ActionSystem extends ActionBase
{
	protected $actionStatus = false;

	protected $errorMessage = '';
	protected $successMessage = '';

	public function logic()
	{

		$input = Input::getInput();

		if(!$this->ioHandler->autoSubmit())
		{
			$form = new Form(get_class($this));
			$form->createInput('confirm')->setLabel('Confirm')->setType('checkbox')->addRule('required');
			$this->form = $form;

			if(!$form->checkSubmit())
				return;
		}

		$this->actionStatus = $this->systemAction();
	}

	abstract protected function systemAction();

	public function viewText()
	{
		return ($this->actionStatus) ? $this->successMessage : $this->errorMessage;
	}

	public function viewAdmin()
	{
		if(!$this->actionStatus)
		{
			if(isset($this->form))
			{
				if($this->form->wasSubmitted())
					$output = $this->errorMessage;

				return $output . $this->form->getFormAs('Html');
			}else{
				return $this->errorMessage;
			}
		}

		return $this->successMessage;
	}
}

?>