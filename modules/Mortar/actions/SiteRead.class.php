<?php

class MortarActionSiteRead extends ModelActionLocationBasedRead
{
	public $adminSettings = array('headerTitle' => 'Installer',
									'tab' => 'Main');

	public function logic()
	{
		$query = Query::getQuery();
		if(isset($query['id']))
			throw new ResourceNotFoundError('Invalid file name presented to site class.');
	}


	public function viewAdmin($page)
	{
		if(!ActiveUser::isLoggedIn())
			throw new AuthenticationError();

		$query = Query::getQuery();

		if($query['tab'])
			$this->adminSettings['tab'] = $query['tab'];

		return parent::viewAdmin($page);
	}

	public function viewHtml($page)
	{
		$location = $this->model->getLocation();
		$indexChild = $location->getChildByName('index');

		if($indexChild)
		{
			$url = new Url();
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