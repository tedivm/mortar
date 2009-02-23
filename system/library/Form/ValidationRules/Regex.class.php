<?php

class FormValidationRegex extends FormValidationAbstract
{
	protected $regex;

	public function validate()
	{
		if(!isset($this->regex))
			$this->regex = $this->argument;

		if(preg_match($this->regex, $this->value) > 0)
			return true;

		$this->addError('');
	}
}

?>