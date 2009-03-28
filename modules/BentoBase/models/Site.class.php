<?php

class BentoBaseModelSite extends AbstractModel
{
	static public $type = 'Site';
	protected $table = 'sites';
	protected $allowedParents = array('Root');

	public function addUrl($url, $ssl = false, $primary = false)
	{
		$id = $this->getId();

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
		$row = new ObjectRelationshipMapper('urls');
		$row->urlPath = $this->content['primaryUrl'];

		if($row->select(1))
		{
			$url = ($ssl && $row->ssl == 1) ? 'https://' : 'http://';
			$url .= $url->path . '/';
			return $url;

		}else{
			return false;
		}
	}

	public function save($parent = null)
	{
		if(!parent::save($parent))
			return false;

		return true;
	}

}

?>