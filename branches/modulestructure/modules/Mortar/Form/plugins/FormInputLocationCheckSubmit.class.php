<?php

class MortarCorePluginFormInputLocationCheckSubmit
{
	protected $input;

	protected $inputName = 'location';

	public function setInput(FormInput $input)
	{
		if($input->type != $this->inputName)
			return;

		$this->input = $input;

	}

	public function processInput($inputHandler)
	{
		$name = $this->input->getName();
		if(isset($inputHandler[$name]))
		{
			if($value = $this->inputToValue($inputHandler[$name]))
				$inputHandler[$name] = $value;

		}
	}

	protected function inputToValue($input)
	{
		if(!($startid = $this->input->property('startid'))) {
			$startid = 1;
		}

		if($loc = Location::getIdByPath($input, $startid))
			return $loc;
		return false;
	}
}

?>