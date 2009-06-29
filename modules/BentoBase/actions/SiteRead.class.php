<?php

class BentoBaseActionSiteRead extends ModelActionLocationBasedRead
{
	public $adminSettings = array('headerTitle' => 'Installer',
									'tab' => 'Main');

	protected $cacheExpirationOffset = -3600;

	public function logic()
	{

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

		if(is_numeric($this->model['defaultChild']))
		{

			$childrenArray = array($this->model['defaultChild']);
			$childId = $this->model['defaultChild'];

			while($location = new Location($childId))
			{
				$type = $location->getType();

				if($type == 'Site' || $type == 'Directory')
				{
					$resource = $location->getResource();
					if(is_numeric($location['defaultChild']))
					{
						if(in_array($location['defaultChild'], $childrenArray))
							throw new BentoError('Redirect look detected');

						$childrenArray[] = $location['defaultChild'];
						$childId = $location['defaultChild'];
					}else{
						break;
					}
				}else{
					break;
				}
			}

			// redirect


			$url = new Url();
			$url->location = $location;

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