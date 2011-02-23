<?php

class MortarFormPluginFormInputTitleToHtml implements FormToHtmlHook

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
			$this->nameBox 	= (isset($nameBox) && $nameBox !== false) 
					? $nameBox->property('id')
					: '';
		}

		$this->input = $input;
	}

	public function getCustomJavaScript()
	{
                $form = $this->input->getForm();
                $name = '"#' . $this->nameBox . '"';
                $js = (isset($this->nameBox) && ($this->nameBox !== '')) 
                	? array('$("#'.$form->name.'").MortarFormSlugify('.$name.');')
                	: null;
                return $js;
	}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}

}

?>