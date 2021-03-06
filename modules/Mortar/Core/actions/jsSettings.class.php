<?php

class MortarCoreActionjsSettings extends ActionBase
{
	static $requiredPermission = 'Read';


	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$cacheControl = 'must-revalidate,max-age=' . $offset;
		$this->ioHandler->addHeader('Cache-Control', $cacheControl);
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

		$output['url']['javascript'] = ActiveSite::getLink('javascript');

		return $output;
	}

}


?>