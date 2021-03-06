<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Environment
 */

/**
 * This class returns the current active site
 *
 * @package System
 * @subpackage Environment
 */
class ActiveSite
{
	/**
	 * This is the current active site
	 *
	 * @access protected
	 * @static
	 * @var model
	 */
	protected static $site;

	/**
	 * This is a link to the current site
	 *
	 * @deprecated
	 * @static
	 * @var string
	 */
	public static $currentLink;

	/**
	 * Returns the current active site
	 *
	 * @cache urlLookup *url
	 * @static
	 * @return Model
	 */
	public static function getSite()
	{
		if(is_null(self::$site))
		{
			if(defined('STDIN') || !isset($_SERVER['REQUEST_METHOD'])) {
				$cache = CacheControl::getCache('defaultSite', 'cli');
				$siteId = $cache->getData();

				if($cache->isStale()) {
					$siteRecord = new ObjectRelationshipMapper('sites');
					$siteId = ($siteRecord->select(1)) ? $siteRecord->site_id : false;
					$cache->storeData($siteId);			
				}

				if($siteId) {
					self::$site = ModelRegistry::loadModel('Site', $siteId);
					return self::$site;
				} else {
					return false;
				}
			}

			if(INSTALLMODE || !isset($_SERVER['SERVER_NAME']))
				return false;

			$ssl = isset($_SERVER['HTTPS']);
			$url = $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER));

			$url = rtrim($url, '/');

			$cache = CacheControl::getCache('urlLookup', $url);
			$siteId = $cache->getData();

			if($cache->isStale() || !is_numeric($siteId))
			{
				$siteRecord = new ObjectRelationshipMapper('sites');
				$siteRecord->primaryUrl = $url;
				$siteId = ($siteRecord->select(1)) ? $siteRecord->site_id : false;

				if(!$siteId)
				{
					$urlRecord = new ObjectRelationshipMapper('urls');
					$urlRecord->path = $url;
					$siteId = ($urlRecord->select(1)) ? $urlRecord->site_id : false;
				}

				if(!$siteId)
				{
					$urlRecord = new ObjectRelationshipMapper('urls');
					$urlRecord->path = 'default';
					$siteId = ($urlRecord->select(1)) ? $urlRecord->site_id : false;
				}

				$cache->storeData($siteId);
			}

			if($siteId === false)
			{
				self::$site = false; // this should eventually be the default or main site.
			}else{
				self::$site = ModelRegistry::loadModel('Site', $siteId);
			}
		}

		return self::$site;
	}

	/**
	 * Returns a link to the current site. If passed an argument it will append the extra path information
	 *
	 * @param string|null $name this corresponds to various directories, such as the theme and javascript libraries
	 * @return string
	 */
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