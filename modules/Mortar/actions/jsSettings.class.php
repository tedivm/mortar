<?php

class MortarActionjsSettings extends ActionBase
{
	static $requiredPermission = 'Read';


	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate('D, d M y H:i:s T', time() + $offset));

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