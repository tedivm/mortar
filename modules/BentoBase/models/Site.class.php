<?php

class BentoBaseModelSite extends LocationModel
{
	static public $type = 'Site';
	protected $table = 'sites';
	public $allowedChildrenTypes = array('Directory');

	public function addUrl($url, $ssl = false, $primary = false)
	{
		$id = $this->getId();

		$url = rtrim($url, '/');

		if(!is_numeric($id))
			throw new BentoError('Site must be saved before attaching urls');

		if(strpos($url, 'http') === 0)
		{
			$offset = 7;
			if(strpos($url, 'https') === 0)
				$offset = 8;

			$url = substr($url, $offset);
		}

		$domainRecord = new ObjectRelationshipMapper('urls');
		$domainRecord->site_id = $id;
		$domainRecord->path = $url;
		$domainRecord->sslEnabled = ($ssl) ? 1 : 0;


		if($domainRecord->save())
		{
			if($primary)
			{
				$this->content['primaryUrl'] = $url;
				$this->save();
			}

			return true;
		}else{
			return false;
		}
	}

	public function removeUrl($url)
	{
		$id = $this->getId();
		if(!is_numeric($id))
			throw new BentoError('Can\'t remove domains from non-existant site.');

		if(strpos($url, 'http') === 0)
		{
			$offset = 7;
			if(strpos($url, 'https') === 0)
				$offset = 8;

			$url = substr($url, $offset);
		}

		$domainRecord = new ObjectRelationshipMapper('urls');
		$domainRecord->site_id = $id;
		$domainRecord->urlPath = $url;
		return $domainRecord->delete(1);
	}

	public function getUrl($ssl = false)
	{
		$cache = new Cache('models', 'site', $this->id, 'url', ($ssl) ? 1:0);
		$url = $cache->getData();

		if($cache->isStale())
		{
			$row = new ObjectRelationshipMapper('urls');
			$row->path = $this->content['primaryUrl'];
			$row->site_id = $this->getId();

			if($row->select(1))
			{
				$url = ($ssl && $row->sslEnabled == 1) ? 'https://' : 'http://';
				$url .= rtrim($row->path, '/') . '/';
			}else{
				$url = false;
			}
			$cache->storeData($url);
		}
		return $url;
	}

}

?>