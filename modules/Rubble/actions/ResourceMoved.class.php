<?php

class RubbleActionResourceMoved extends ActionBase
{
	public function logic()
	{
		$query = Query::getQuery();

		$redirectUrl = Query::getUrl();
		$redirectUrl->format = $query['format'];

		$this->ioHandler->setStatusCode(301);
		$this->ioHandler->addHeader('Location', (string) $redirectUrl);
	}

	public function viewHtml()
	{
		return '';
	}

}

?>