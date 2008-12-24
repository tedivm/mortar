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


	public function viewAdminForm()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
			{
				$this->adminSuccess();
			}else{
				$this->adminError();
			}
		}else{

		}

		$info = InfoRegistry::getInstance();
		if(isset($info->Get['message']) && method_exists($this, 'adminMessage'))
		{
			$this->adminMessage($info->Get['message']);
		}


		$output .= $this->form->makeDisplay();
		return $output;
	}

	protected function adminSuccess()
	{

	}

	protected function adminError()
	{

	}

	protected function adminMessage($messageId)
	{

	}
}

?>