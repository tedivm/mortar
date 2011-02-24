<?php

class MortarFormPluginFormInputMembergroupToHtml extends MortarFormPluginFormInputUserToHtml
{
	protected function runCheck(MortarFormInput $input)
	{
		if($input->type != 'membergroup')
			return false;

		return true;
	}

	protected function getUrl(MortarFormInput $input)
	{
		$url = new Url();
		$url->module = PackageInfo::loadByName('Mortar', 'Core');
		$url->format = 'json';
		$url->action = 'MemberGroupLookUp';

		return $url;
	}

	protected function getString($id, $baseString)
	{
		$memberGroup = ModelRegistry::loadModel('MemberGroup', $id);
		if($memberGroup->getId())
			$baseString .= $memberGroup['name'] . ', ';

		return $baseString;
	}
}

?>