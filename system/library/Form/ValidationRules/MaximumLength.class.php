<?php

class FormValidationMaximumLength extends FormValidationAbstract
{
	protected $error = '';

	public function validate()
	{
		if($this->value <= $this->argument)
			return true;

		$this->addError('value should be at most ' . $this->argument . 'charactors.');
		return false;
	}
}

?>