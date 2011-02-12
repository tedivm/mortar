<?php

class MortarCorePluginFormInputDatetimeToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if($input->type != 'datetime')
			return;

		$input->setType('input');
		$input->property('datetime', 'yes');

		$this->input = $input;
	}

	public function getCustomJavaScript()
	{
		$form = $this->input->getForm();
		return array('$("#'.$form->name.'").MortarFormDatepicker();');
	}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}

}

?>