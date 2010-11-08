<?php

class MortarActionDirectoryRead extends ModelActionLocationBasedRead
{
	public function viewAdmin($page)
	{
		if(!ActiveUser::isLoggedIn())
			throw new AuthenticationError();

		$query = Query::getQuery();

		return parent::viewAdmin($page);
	}

	public function viewHtml($page)
	{
		$location = $this->model->getLocation();
		if($default = $location->getMeta('defaultPage', true)) {
			$indexChild = Location::getLocation($default);
		} else {
			$indexChild = $location->getChildByName('index');
		}

		if($indexChild)
		{
			$url = new Url();
			$url->format = 'Html';
			$url->location = $indexChild;
			$this->ioHandler->addHeader('Location', (string) $url);
			$this->ioHandler->setStatusCode(307);
			return (string) $url;

		}elseif($this->model['allowIndex']){
			// show index
		}
	}

	public function viewXml()
	{
		$xml = ModelToXml::convert($this->model, $this->requestHandler);
		return $xml;
	}

	public function viewJson()
	{
		$array = ModelToArray::convert($this->model, $this->requestHandler);
		return $array;
	}
}

?>