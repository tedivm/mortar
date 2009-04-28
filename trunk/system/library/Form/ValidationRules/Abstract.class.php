<?php

abstract class FormValidationAbstract
{
	protected $errors = array();
	protected $input;
	protected $userInput;
	protected $argument;
	protected $value;
	protected $form;

	protected $error;

	public function attachInput(FormInput $input, $userValue, $argument = null)
	{
		$this->input = $input;
		$this->argument = $argument;
		$this->form = $input->getForm();
		$this->value = $userValue;
	}

	abstract public function validate();

	public function getErrors()
	{
		if(count($this->errors) < 1)
			return false;

		return $this->errors;
	}

	protected function addError($errorText = null)
	{
		if(is_null($errorText))
			$errorText = $this->error;

		$this->errors[] = $errorText;
	}

}

?>