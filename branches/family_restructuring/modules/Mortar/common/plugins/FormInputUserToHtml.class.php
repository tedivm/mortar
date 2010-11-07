<?php

class MortarPluginFormInputUserToHtml implements FormToHtmlHook
{
	protected $input;

	public function setInput(FormInput $input)
	{
		if(!$this->runCheck($input))
			return;

		if(isset($input->properties['value']))
		{
			$values = array();
			if(isset($input->properties['multiple']) && $input->properties['multiple'] == true)
			{
				if($input->properties['value'] instanceof FilteredArray)
				{
					$values = $input->properties['value']->getArrayCopy();
				}elseif(is_array($input->properties['value'])){
					$values = $input->properties['value'];
				}
			}else{
				if(is_numeric($input->properties['value']))
					$values = array($input->properties['value']);
			}
			sort($values);

			$valueString = '';
			foreach($values as $id)
			{
				$valueString = $this->getString($id, $valueString);
			}
			$valueString = rtrim($valueString, ', ');
			$input->property('value', $valueString);
		}

		$url = $this->getUrl($input);
		$input->setType('input');
		$input->property('autocomplete', (string) $url);
		$this->input = $input;

	}

	protected function runCheck(FormInput $input)
	{
		if($input->type != 'user')
			return false;

		return true;
	}

	protected function getUrl(FormInput $input)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName(null, 'Mortar');
		$url->format = 'json';
		$url->action = 'UserLookUp';

		if(isset($input->properties['membergroup']))
		{
			$url->m = $input->properties['membergroup'];
			unset($input->properties['membergroup']);
		}
		return $url;
	}

	protected function getString($id, $baseString)
	{
		if($user = ModelRegistry::loadModel('User', $id))
			$baseString .= $user['name'] . ', ';

		return $baseString;
	}


	public function getCustomJavaScript(){}
	public function overrideHtml(){}
	public function createOverriddingHtml($sectionHtml){}
	public function setCustomHtml($inputHtml){}
}

?>