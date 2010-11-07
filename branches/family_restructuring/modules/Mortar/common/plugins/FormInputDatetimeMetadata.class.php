<?php

class MortarPluginFormInputDatetimeMetadata implements FormMetadataHook
{
	public function getMetadataOptions(FormInput $input)
	{
		$inputOptions = array();

		if(isset($input->properties['datetime']) && $input->properties['datetime'])
		{
			$inputOptions['datetime']['data'] = $input->properties['datetime'];
			unset($input->properties['datetime']);
		}

		return $inputOptions;
	}
}

?>
