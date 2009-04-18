<?php

class ActiveSite
{
	protected static $site;
	public static $currentLink;

	public static function getSite()
	{

		if(is_null(self::$site))
		{
			$ssl = isset($_SERVER['HTTPS']);
			$url = $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));

			$url = rtrim($url, '/');

			if(INSTALLMODE)
				return false;

			$urlRecord = new ObjectRelationshipMapper('urls');
			$urlRecord->path = $url;


			if($urlRecord->select(1))
			{
				$siteHandler = importModel('Site');
				$site = new $siteHandler($urlRecord->site_id);
				self::$site = $site;
			}else{
				self::$site = false;
			}
		}

		return self::$site;
	}

	public static function getLink($name = null)
	{
		if(is_null(self::$currentLink))
		{
			$ssl = isset($_SERVER['HTTPS']);
			$url = $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));
			self::$currentLink = 'http' . ($ssl ? 's' : '') .  '://' . $url;
		}

		$link = self::$currentLink;

		if($name)
		{
			$config = Config::getInstance();

			if(isset($config['url'][$name]))
				$link .= $config['url'][$name];
		}
		return $link;
	}



}

?>