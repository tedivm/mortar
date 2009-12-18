<?php

class MortarPluginFormInputMembergroupToHtml extends MortarPluginFormInputUserToHtml
{
	protected function runCheck(FormInput $input)
	{
		if($input->type != 'membergroup')
			return false;

		return true;
	}

	protected function getUrl(FormInput $input)
	{
		$url = new Url();
		$url->module = 'Mortar';
		$url->format = 'json';
		$url->action = 'MemberGroupLookUp';

		return $url;
	}

	protected function getString($id, $baseString)
	{
		$memberGroup = ModelRegistry::loadModel('MemberGroup', $id);
		if($memberGroup->getId())
			$baseString .= $memberGroup->name . ', ';

		return $baseString;
	}
}

?>