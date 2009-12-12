<?php

class MortarPluginFormInputLocationToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if($input->type != 'location')
			return;


		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'json';
		$url->action = 'LocationLookup';

		if(isset($input->properties['parent']))
		{
			$url->parent = $input->properties['parent'];
			unset($input->properties['parent']);
		}

		$input->setType('input');
		$input->property('autocomplete', $url);
		$this->input = $input;

	}
	public function getCustomJavaScript(){}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>