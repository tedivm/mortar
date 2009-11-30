<?php

abstract class FormAction extends ActionBase
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
		$form = new $formName(get_class($this));

		return $form;
	}

	abstract protected function processInput($inputHandler);


	public function viewAdminForm()
	{
		$output = '';
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

		$query = Query::getQuery();
		if(isset($query['message']) && method_exists($this, 'adminMessage'))
		{
			$this->adminMessage($query['message']);
		}


		$output .= $this->form->getFormAs('Html');
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