<?php

class MortarPluginFormInputAutocompleteMetadata implements FormMetadataHook
{
	public function getMetadataOptions(FormInput $input)
	{
		$inputOptions = array();

		if(isset($input->properties['autocomplete']) && $input->properties['autocomplete'])
		{
			$inputOptions['autocomplete']['data'] = $input->properties['autocomplete'];
			if(isset($input->properties['multiple']))
			{
				$inputOptions['autocomplete']['options']['multiple'] = true;
			}
			unset($input->properties['autocomplete']);
		}

		return $inputOptions;
	}
}

?>
