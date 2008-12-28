<?php

abstract class ModelActionEdit extends ModelAction
{
	protected $form;

	protected function logic()
	{
		$this->form = $this->getForm();

		if($this->form->checkSubmit())
		{
			$this->formStatus = ($this->processInput($this->form->getInputHandler()));
		}
	}

	abstract protected function processInput($formInput);

	abstract protected function getForm();




	protected function viewForm()
	{
		if($this->form->wasSubmitted())
		{
			if($this->formStatus)
			{
				$this->formSuccess();
			}else{
				$this->formError();
			}
		}

		$info = InfoRegistry::getInstance();
		if(isset($info->Get['message']) && method_exists($this, 'adminMessage'))
		{
			$this->adminMessage($info->Get['message']);
		}

		$output .= $this->form->makeDisplay();
		return $output;
	}


	protected function formSuccess()
	{
		$this->redirect('Success');
	}

	protected function formError()
	{
		$this->redirect('Error');
	}

	protected function formMessage($messageIdentifier)
	{

	}

	protected function redirect($message)
	{
		$redirect = $_SERVER['PHP_SELF'] . '&message=' . $message;
		header('Location:' . $redirect);
	}

}

?>