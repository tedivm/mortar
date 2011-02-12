<?php

class MortarCorePluginFormInputDatetimeCheckSubmit extends MortarCorePluginFormInputUserCheckSubmit
{
	protected $inputName = 'datetime';

	protected function inputToValue($input)
	{
		return strtotime($input);
	}
}

?>
