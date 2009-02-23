<?php

class FormValidationMinimumWords extends FormValidationAbstract
{
	protected $error = '';

	public function validate()
	{
		if(str_word_count($this->value) >= $this->argument)
			return true;

		$this->addError();
		return false;
	}
}

?>