<?php

class MortarPluginFormInputMembergroupCheckSubmit extends MortarPluginFormInputUserCheckSubmit
{
	protected $inputName = 'membergroup';

	protected function inputToValue($input)
	{
		if($id = MemberGroup::lookupIdbyName($input))
			return $id;
		return false;
	}
}

?>