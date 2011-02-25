<?php

class GraffitiPluginFormInputTagCheckSubmit extends MortarCorePluginFormInputUserCheckSubmit
{
	protected $inputName = 'tag';

	protected function inputToValue($input)
	{
		if($id = GraffitiTagLookUp::getTagId($input))
			return $id;
		return false;
	}
}

?>