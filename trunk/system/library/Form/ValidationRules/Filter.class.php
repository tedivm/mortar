<?php

class FormValidationFilter extends FormValidationAbstract
{
	protected $filter;

	public function validate()
	{
		if(!isset($this->filter))
			$this->filter = $this->argument;

		if(filter_var($this->value, $this->filter))
			return true;

		$this->addError('');
		return false;
	}
}


?>