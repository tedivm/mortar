<?php

class MortarFormPluginFormInputTemplateToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(MortarFormInput $input)
	{
		if(!$this->runCheck($input))
			return;

		$url = $this->getUrl($input);
		$input->setType('input');
		
		$input->property('autocomplete', (string) $url);
		$this->input = $input;

	}

	protected function runCheck(MortarFormInput $input)
	{
		if($input->type != 'template')
			return false;

		return true;
	}

	protected function getUrl(MortarFormInput $input)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName('Mortar', 'Core');
		$url->format = 'json';
		$url->action = 'TemplateLookUp';

		return $url;
	}

	public function getCustomJavaScript()
	{
		$id = $this->input->property('id');

		$code = 'var class = $("[id=\'' . $id . '\']").attr("using");

			var getAcUrl = function (theme) {
				var meta = $("[id=\'' . $id . '\']").metadata();
				var url = meta.autocomplete.data + "?t=" + theme;
				return url;
			};

			$("[id=\'' . $id . '\']").focus(function() {
				var url = getAcUrl($("[name=\'" + class + "\']").val());
				$("[id=\'' . $id . '\']").autocomplete({source: url});
			});

			';

		return array($code);
	}

	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>