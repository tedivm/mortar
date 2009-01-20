<?php

class Site
{
	public $siteId;
	public $location;
	public $name;

	public $ssl;
	public $domain;

	public $meta;



	public function loadByUrl($baseUrl, $ssl = false)
	{
		$cache = new Cache('sites', 'lookup', ($ssl) ? 'https' : 'http', $baseUrl);
		$siteInfo = $cache->get_data();

		if(!$cache->cacheReturned)
		{

			$url = new ObjectRelationshipMapper('urls');

			$url->urlPath = $baseUrl;


			if($ssl)
				$url->urlSSL = '1';

			$db = dbConnect('default_read_only');
			$stmtSite = $db->stmt_init();

			if($url->select(1))
			{
				$stmtSite->prepare('SELECT * FROM sites WHERE site_id = ?');
				$stmtSite->bind_param_and_execute('i', $url->site_id);

				if($row = $stmtSite->fetch_array())
				{
					$siteInfo['siteId'] = $row['site_id'];
					$siteInfo['siteLocationId'] = $row['location_id'];
					$siteInfo['siteName'] = $row['name'];
				}

			}

			if(!isset($siteInfo['siteId']))
			{
				if($queryResults = $db->query('SELECT * FROM sites ORDER BY site_id DESC LIMIT 1'))
				{
					$row = $queryResults->fetch_array();
					$siteInfo['siteId'] = $row['site_id'];
					$siteInfo['siteLocationId'] = $row['location_id'];
					$siteInfo['siteName'] = $row['name'];
				}
			}


			if(isset($siteInfo['siteId']))
			{
				$mainUrls = new ObjectRelationshipMapper('urls');
				$mainUrls->site_id = $siteInfo['siteId'];
				$mainUrls->urlAlias = 0;
				$mainUrls->select();
				$mainUrls->select();

				$results = $mainUrls->resultsToArray();
				foreach ($results as $url)
				{
					if($mainUrls->urlSSL == 1)
					{
						$siteInfo['mainSSLUrl'] = 'https://' . $mainUrls->urlPath;
					}else{
						$siteInfo['mainUrl'] = 'http://' . $mainUrls->urlPath;
					}
				}
			}
			$cache->store_data($siteInfo);
		}

		$this->siteId = $siteInfo['siteId'];
		$this->location = new Location($siteInfo['siteLocationId']);
		$this->name = $siteInfo['siteName'];
		$this->ssl = $siteInfo['mainSSLUrl'];
		$this->domain = $siteInfo['mainUrl'];

	}

	public function meta($name, $value = '')
	{
		if($value != '')
		{
			return $this->meta[$name];
		}else{
			return ($this->meta[$name] = $value);
		}
	}

	public function getLocation()
	{
		return $this->location;
	}


	protected function loadMeta()
	{
		if(!is_numeric($this->siteId))
		{
			return;
		}

		$cache = new Cache('sites', $this->siteId, 'meta');
		$meta = $cache->get_data();

		if($cache->cacheReturned)
		{
			$metaRecords = new ObjectRelationshipMapper('site_meta');
			$metaRecords->site_id = $this->siteId;

			while($metaRecords->select())
			{
				$meta[$metaRecords->name] = $metaRecords->value;
			}
			$cache->store_data($meta);
		}

		$this->meta = $meta;
	}


}


class ActiveSite extends Site
{
	static $instance;
	public $currentLink;

	protected function __construct()
	{
		$ssl = ($_SERVER['HTTPS']);

		$url = $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));
		$this->currentLink = 'http' . ($ssl ? 's' : '') .  '://' . $url;

		if(!INSTALLMODE)
			$this->loadByUrl($url, $ssl);
	}

	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}

	public function getLink($name = false)
	{

		$info = InfoRegistry::getInstance();
		$append = $info->Configuration['url'][$name];
		$link = (strlen($append) > 0 || $name === false) ? $this->currentLink . $append : false;
		return $link;
	}

}

?>