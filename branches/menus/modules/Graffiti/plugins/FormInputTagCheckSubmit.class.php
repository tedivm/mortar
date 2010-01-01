<?php

class GraffitiPluginFormInputTagCheckSubmit extends MortarPluginFormInputUserCheckSubmit
{
	protected $inputName = 'tag';

	protected function inputToValue($input)
	{
		if(TagLookUp::getTagId($input))
			return $id;
		return false;
	}
}

?>