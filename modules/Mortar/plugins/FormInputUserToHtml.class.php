<?php

class MortarPluginFormInputUserToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if($input->type != 'user')
			return;


		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'json';
		$url->action = 'UserLookUp';

		if(isset($input->properties['membergroup']))
		{
			$url->membergroup = $input->properties['membergroup'];
			unset($input->properties['membergroup']);
		}

		$input->setType('input');
		$input->property('autocomplete', (string) $url);
		$this->input = $input;

	}
	public function getCustomJavaScript(){}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>