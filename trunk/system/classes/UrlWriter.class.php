<?php

class UrlWriter
{
	static $enableRewrite = false;

	static function getUrl($attributes)
	{
		if(defined('XDEBUG_PROFILE') && XDEBUG_PROFILE)
			$attributes['XDEBUG_PROFILE'] = 1;

		$base = self::getBase($attributes);
		$path = self::makePath($attributes);
		$url = $base . $path;

		if(count($attributes) > 0)
		{
			$query = http_build_query($attributes);
			$delimiter = (self::$enableRewrite) ? '?' : '&';
			$url .= $delimiter . $query;
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
			$url .= BASE_URL . 'index.php?p=';
			return $url;
		}

		if(isset($attributes['locationId']))
		{
			$location = new Location($attributes['locationId']);
			$siteId = $location->getSite();
			$site = ModelRegistry::loadModel('Site', $siteId);
			$url = $site->getDomainUrl($ssl);
		}else{
			$site = ActiveSite::getSite();
			$url = $site->getDomainUrl($ssl);
		}

		if(!self::$enableRewrite)
			$url .= 'index.php?p=';

		return $url;
	}

	static protected function makePath(&$attributes)
	{
		$path = '';

		if(isset($attributes['iohandler']) && $attributes['iohandler'] === 'rest')
		{
			$path = 'rest/';
			unset($attributes['iohandler']);
		}


		if(isset($attributes['format']) && strtolower($attributes['format']) === 'admin')
		{
			$path = 'admin/';
			unset($attributes['format']);
		}

		if(isset($attributes['module']))
		{
			$path .= 'module/' . $attributes['module'];
			unset($attributes['module']);

		}elseif(isset($attributes['locationId'])){

			$location = new Location($attributes['locationId']);
			unset($attributes['locationId']);

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
		}elseif(isset($attributes['type'])){

			if(in_array($attributes['type'], UrlReader::$resourceMaps))
			{
				$path .= array_search($attributes['type'], UrlReader::$resourceMaps) . '/';
			}else{
				$path .= 'resources/' . $attributes['type'] . '/';
			}
			unset($attributes['type']);

			if(isset($attributes['id']))
			{
				$path .= $attributes['id'] . '/';
				unset($attributes['id']);
			}

		}

		/*

		if(isset($attributes['action']))
		{
			if($attributes['action'] !== 'Read')
				$path .= $attributes['action'] . '/';
			unset($attributes['action']);
		}
		*/

		$path = rtrim($path, '/');

		if(strlen($path) > 1 && isset($attributes['format']))
		{
			$path .= '.' . strtolower($attributes['format']);
			unset($attributes['format']);
		}

		return $path;
	}


}

$config = Config::getInstance();
UrlWriter::$enableRewrite = (isset($config['url']['modRewrite']) && (bool) $config['url']['modRewrite']);
?>