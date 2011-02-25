<?php

class MortarFormPluginFormInputDatetimeCheckSubmit extends MortarFormPluginFormInputUserCheckSubmit
{
	protected $inputName = 'datetime';

	protected function inputToValue($input)
	{
		return strtotime($input);
	}
}

?>
