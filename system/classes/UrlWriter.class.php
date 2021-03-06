<?php

class UrlWriter
{
	static $enableRewrite = false;

	static function getUrl($attributes)
	{
		if(defined('XDEBUG_PROFILE') && XDEBUG_PROFILE)
			$attributes['XDEBUG_PROFILE'] = 1;

		if(isset($attributes['iotype']) && strtolower($attributes['iotype']) == 'http')
			unset($attributes['iotype']);

		$url = self::getBase($attributes);
		$path = self::makePath($attributes);

		if(strlen($path))
		{
			if(!self::$enableRewrite || (defined('INSTALLMODE') && INSTALLMODE == true))
				$url .= '?p=';

			$url .= $path;
		}

		if(isset($attributes['format']) && $attributes['format'] == 'Html')
			unset($attributes['format']);

		if(count($attributes) > 0)
		{
			$query = http_build_query($attributes);
			$url .= (self::$enableRewrite) ? '?' : '&';
			$url .= $query;
		}elseif(substr($url, -9) == 'index.php'){
			$url = substr($url, 0, strpos($url, 'index.php'));
		}

		return $url;
	}

	static protected function getBase(&$attributes)
	{
		if(isset($attributes['ssl']))
		{
			$ssl = (bool) $attributes['ssl'];
			unset($attributes['ssl']);
		}else{
			$ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		}

		if(defined('INSTALLMODE') && INSTALLMODE == true)
		{
			$url = $ssl ? 'https://' : 'http://';
			$url .= BASE_URL . 'index.php';
			return $url;
		}

		try{


			if(isset($attributes['locationid']))
			{
				$location = Location::getLocation($attributes['locationid']);
				$siteId = $location->getSite();

				if(!$siteId)
					throw new Exception('Unable to get Site ID');

				$site = ModelRegistry::loadModel('Site', $siteId);
				$url = $site->getDomainUrl($ssl);
			}else{
				$site = ActiveSite::getSite();
				$url = $site->getDomainUrl($ssl);
			}

		}catch(Exception $e){

			if(isset($attributes['locationid']))
			{
				$site = ActiveSite::getSite();
				$url = $site->getDomainUrl($ssl);
			}else{
				$url = BASE_URL;
			}
		}

		if(!self::$enableRewrite)
			$url .= 'index.php';

		return $url;
	}

	static protected function makePath(&$attributes)
	{
		$path = '';

		if(isset($attributes['iotype']) && $attributes['iotype'] === 'rest')
		{
			$path = 'rest/';
			unset($attributes['iotype']);
		}

		if(isset($attributes['format']) && strtolower($attributes['format']) === 'admin')
		{
			$path = 'admin/';
			unset($attributes['format']);
		}

		if(isset($attributes['module']))
		{
			$path = self::buildModulePath($path, $attributes);
		}elseif(isset($attributes['type']) && (!isset($attributes['action']) || $attributes['action'] != 'Add') ){
			$path = self::buildResourcePath($path, $attributes);
		}elseif(isset($attributes['locationid'])){
			$path = self::buildLocationPath($path, $attributes);
		}

		$path = rtrim($path, '/');

		if(strlen($path) > 1 && isset($attributes['format']))
		{
			$path .= '.' . strtolower($attributes['format']);
			unset($attributes['format']);
		}

		return $path;
	}

	static function buildLocationPath($path, &$attributes)
	{
		$location = Location::getLocation($attributes['locationid']);
		unset($attributes['locationid']);

		// here we will iterate back to the site, creating the path to the model in reverse.
		$tempLoc = $location;
		$locationString = '';
		while($tempLoc->getType() != 'Site')
		{
			$locationString = str_replace(' ', '-', $tempLoc->getName()) . '/' . $locationString;
			if(!$parent = $tempLoc->getParent())
				break;
			$tempLoc = $parent;
		}

		if(strlen($locationString) > 0)
			$path .= $locationString . '/';

		if(isset($attributes['action']) && $attributes['action'] == 'Read'){
			unset($attributes['action']);
		}

		return $path;
	}

	static function buildResourcePath($path, &$attributes)
	{
		if($shortCut = Url::getShortcutFromModel($attributes['type']))
		{
			$path .= $shortCut . '/';
		}else{
			$path .= 'resources/' . $attributes['type'] . '/';
		}
		unset($attributes['type']);

		if(isset($attributes['id']))
		{
//			if($attributes['action'] != 'Index')
	//			$path .= $attributes['id'] . '/';

			$path .= $attributes['id'] . '/';
			unset($attributes['id']);
		}elseif(isset($attributes['action']) && $attributes['action'] == 'Index'){
			unset($attributes['action']);
		}

		return $path;
	}

	static function buildModulePath($path, &$attributes)
	{
		$packageInfo = $attributes['module'];

		$family = $packageInfo->getFamily();
		$module = $packageInfo->getName();

		$path .= 'module/';

		if($family != 'orphan')
			$path .= $family . '/';

		$path .= $module;

		unset($attributes['module']);

		if(isset($attributes['action']))
		{
			$path .= '/' . $attributes['action'];
			unset($attributes['action']);

			if(isset($attributes['id']))
			{
				$path .= '/' . $attributes['id'];
				unset($attributes['id']);
			}
		}

		return $path;
	}

}

$config = Config::getInstance();
UrlWriter::$enableRewrite = (isset($config['url']['modRewrite']) && (bool) $config['url']['modRewrite']);
?>