<?php

class MortarFormPluginFormInputLocationToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if(!$this->runCheck($input))
			return;

		if(isset($input->properties['value']))
		{
			if(isset($input->properties['startid']) && is_numeric($input->properties['startid'])) {
				$id = $input->properties['startid'];
			} else {
				$id = 1;
			}

			$valueString = Location::getPathById($input->properties['value'], $id);
			$input->property('value', $valueString);
		}

		$url = $this->getUrl($input);
		$input->setType('input');
		$input->property('autocomplete', (string) $url);
		$this->input = $input;

	}

	protected function runCheck(FormInput $input)
	{
		if($input->type != 'location')
			return false;

		return true;
	}

	protected function getUrl(FormInput $input)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName('Mortar', 'Core');
		$url->format = 'json';
		$url->action = 'LocationLookUp';

		if(isset($input->properties['startid']))
		{
			$url->s = $input->properties['startid'];
		}
		return $url;
	}

	public function getCustomJavaScript(){}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>