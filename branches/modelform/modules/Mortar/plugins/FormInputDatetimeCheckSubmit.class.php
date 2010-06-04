<?php

class MortarPluginFormInputDatetimeCheckSubmit extends MortarPluginFormInputUserCheckSubmit
{
	protected $inputName = 'datetime';

	protected function inputToValue($input)
	{
		return strtotime($input);
	}
}

?>
