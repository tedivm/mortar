<?php

class FormValidationMinimumValue extends FormValidationAbstract
{
	protected $error = '';

	public function validate()
	{
		if($this->value >= $this->argument)
			return true;

		$this->addError('value should be at least ' . $this->argument . 'charactors.');
		return false;
	}
}

?>