<?php

class GraffitiPluginFormInputTagCheckSubmit extends MortarPluginFormInputUserCheckSubmit
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