<?php

class RubbleActionResourceMoved extends ActionBase
{
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

		$this->ioHandler->setStatusCode(301);
		$this->ioHandler->addHeader('Location', (string) $redirectUrl);
	}

	public function viewHtml()
	{
		return '';
	}

	public function checkAuth($action = NULL)
	{
		return true;
	}

}

?>