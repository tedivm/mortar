<?php

class MortarPluginFormInputTemplateToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if(!$this->runCheck($input))
			return;

		$url = $this->getUrl($input);
		$input->setType('input');
		$input->property('autocomplete', (string) $url);
		$this->input = $input;

	}

	protected function runCheck(FormInput $input)
	{
		if($input->type != 'template')
			return false;

		return true;
	}

	protected function getUrl(FormInput $input)
	{
		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'json';
		$url->action = 'TemplateLookUp';

		if(isset($input->properties['theme']))
		{
			$url->t = $input->properties['template'];
		}
		return $url;
	}

	public function getCustomJavaScript()
	{
		$id = $this->input->property('id');

		$code = 'class = $("[id=\'' . $id . '\']").attr("using");
		
			$("[name=\'" + class + "\']").change(function() { 
				$("[id=\'' . $id . '\']").flushCache();
				$("[id=\'' . $id . '\']").setOptions({ extraParams: {t: $(this).val()}});
			});';

				
		return array($code);
	}

	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>