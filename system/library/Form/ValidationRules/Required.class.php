<?php

class FormValidationRequired extends FormValidationAbstract
{
	protected $error = 'Required field not filled out.';

	public function validate()
	{

		if($this->value || is_numeric($this->value) || $this->value === false)
			return true;


		$this->addError();
		return false;
	}


}

?>