<?php


abstract class FormPackageAction extends PackageAction
{
	protected $formStatus = false;
	protected $form;
	protected $formName;

	public function logic()
	{
		$this->form = $this->getForm();

		if($this->form->checkSubmit())
		{
			$this->formStatus = ($this->processInput($this->form->getInputHandler()));
		}
	}

	protected function getForm()
	{
		$formName = $this->formName;
		return new $formName($this->actionName);
	}

	abstract protected function processInput($inputHandler);
}

?>