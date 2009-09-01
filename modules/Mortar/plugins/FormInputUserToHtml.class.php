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
			$url->m = $input->properties['membergroup'];
			unset($input->properties['membergroup']);
		}

		if(isset($input->properties['value']))
		{
			if($input->properties['value'] instanceof FilteredArray)
			{
				$values = $input->properties['value']->getArrayCopy();
			}elseif(is_array($input->properties['value'])){
				$values = $input->properties['value'];
			}else{
				$values = array();
			}
			sort($values);

			$valueString = '';
			foreach($values as $id)
			{
				if($user = ModelRegistry::loadModel('User', $id))
				{
					$valueString .= $user['name'] . ', ';
					$user['name'];
				}
			}
			$valueString = rtrim($valueString, ', ');
		}

		$input->property('value', $valueString);

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