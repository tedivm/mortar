<?php

class MortarPluginFormInputDatetimeToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if($input->type != 'datetime')
			return;

		$input->setType('input');
		$input->property('datetime', 'yes');
		$input->property('class', 'datetime');

		$this->input = $input;
	}

	public function getCustomJavaScript(){}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}

}

?>