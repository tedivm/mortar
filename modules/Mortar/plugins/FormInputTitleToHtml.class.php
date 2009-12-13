<?php

class MortarPluginFormInputTitleToHtml implements FormToHtmlHook

{
	protected $input;
	protected $nameBox;

	public function setInput(FormInput $input)
	{
		if($input->type != 'title')
			return;

		$input->setType('input')->property('slugify', 'yes');

		$slugfield = $input->property('slugfield');
		if(isset($slugfield) && $slugfield !== false) {
			$this->nameBox = $slugfield;	
		} else {
			$form = $input->getForm();
			$nameBox = $form->getInput('location_name', 'location_information');
			$this->nameBox = $nameBox->property('id');
		}

		$this->input = $input;
	}

	public function getCustomJavaScript()
	{
                $form = $this->input->getForm();
                $name = '"#' . $this->nameBox . '"';
                return array('$("#'.$form->name.'").MortarFormSlugify('.$name.');');
	}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}

}

?>