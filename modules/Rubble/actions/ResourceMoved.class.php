<?php

class RubbleActionResourceMoved extends ActionBase
{
	protected $redirectUrl;

	public function logic()
	{
		$query = Query::getQuery();

		$redirectUrl = Query::getUrl();
		$redirectUrl->format = $query['format'];

		if(isset($redirectUrl->locationId) && isset($redirectUrl->module))
		{
			$site = ActiveSite::getSite();
			$location = $site->getLocation();
			if($location->getId() == $redirectUrl->locationId)
				unset($redirectUrl->locationId);
		}

		$this->redirectUrl = $redirectUrl;
		$this->ioHandler->setStatusCode(301);
		$this->ioHandler->addHeader('Location', (string) $redirectUrl);
	}

	public function viewHtml()
	{
		$link = $this->redirectUrl->getLink('new location');
		return '<p>This page has been moved to a ' . trim($link, PHP_EOL) .
				', please wait a moment while we redirect you.</p>';
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>