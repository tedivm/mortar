<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This class returns the current active site
 *
 * @package MainClasses
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
	 * @static
	 * @return Model
	 */
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