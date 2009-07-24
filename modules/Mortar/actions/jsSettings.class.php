<?php

class MortarActionjsSettings extends ActionBase
{
	static $requiredPermission = 'Read';


	public function logic()
	{
		//$info = InfoRegistry::getInstance();
	}

	public function viewJson()
	{
		$output = array();
		$info = InfoRegistry::getInstance();
		$site = $info->Site;

		if($site->currentLink)
			$output['url']['current'] = $site->currentLink;

		if($site->ssl)
			$output['url']['ssl'] = $site->ssl;


		if($site->name)
			$output['name'] = $site->name;

		$urls = ($info->Configuration['url']);

$output['url']['theme'] = ActiveSite::getLink('theme');
$output['url']['module'] = ActiveSite::getLink('module');
$output['url']['javascript'] = ActiveSite::getLink('javascript');

//		$output['url']['adminTheme'] = $urls['theme'];

//		$output['url']['theme'] = $output['url']['current'] . $urls['theme'];
//		$output['url']['modules'] = $output['url']['current'] . $urls['modules'];
//		$output['url']['javascript'] = $output['url']['current'] . $urls['javascript'];

//		$output['url']['base'] = $urls[''];
//		$output[] = 'cheese';





		return $output;
	}

}


?>