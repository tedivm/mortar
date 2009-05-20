<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 */

/**
 * This class creates urls and links for use by the system. The goal is to make the Urls as SEO and user friendly as
 * possible, so various mod_rewrite and path tricks are enabled.
 *
 * @package System
 */
class Url
{
	/**
	 * This array contains the values that need to be passed via the url
	 *
	 * @access protected
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * This function returns a string, the Url
	 *
	 * @cache *type *odule url pathCache
	 * @return string
	 */
	public function __toString()
	{
		$config = Config::getInstance();
		$info = InfoRegistry::getInstance();

		$attributes = $this->attributes;
		ksort($attributes);
		$urlString = '';

		if(!isset($info->Configuration['url']['modRewrite']) || !$info->Configuration['url']['modRewrite'])
		{
			$urlString .= 'index.php?p=';
		}

		if(isset($attributes['format']) && strtolower($attributes['format']) == 'admin')
		{
			$urlString .= 'admin/';
			unset($attributes['format']);
		}

		if(isset($attributes['rest']) && $attributes['rest'])
		{
			$urlString .= 'rest/';
			unset($attributes['rest']);
		}

		if(isset($attributes['module']))
		{
			$urlString .= 'module/' . $attributes['module'] . '/';
			unset($attributes['module']);
			if(isset($attributes['action']))
			{
				if($attributes['action'] != 'Default')
					$urlString .= $attributes['action'] . '/';

				unset($attributes['action']);
			}
		}

		if(isset($attributes['location']))
		{
			if($attributes['location'] instanceof Location)
			{
				$location = $attributes['location'];
				unset($attributes['location']);
			}elseif(is_numeric($attributes['location'])){
				$location = new Location($attributes['location']);
				unset($attributes['location']);
			}

			if(isset($attributes['action']) && $attributes['action'] == 'Read')
				unset($attributes['action']);

		}elseif(isset($attributes['locationId']) && is_numeric($attributes['locationId'])){
			$location = new Location($attributes['locationId']);
			unset($attributes['locationId']);
		}

		if(isset($location))
		{
			$tempLoc = $location;
			while($tempLoc->getType() != 'Site')
			{
				$urlString .= str_replace(' ', '-', $tempLoc->getName()) . '/';
				if(!$parent = $tempLoc->getParent())
					break;
				$tempLoc = $parent;
			}

			$handler = ModelRegistry::getHandler($location->getType());
			$pathCache = new Cache($location->getType(), $handler['module'], 'url', 'pathCache');
			$pathTemplate = $pathCache->getData();

			if(!$pathCache->cacheReturned)
			{
				$pathCacheDisplay = new DisplayMaker();
				if(!$pathCacheDisplay->loadTemplate('UrlPath', $handler['module']))
					if(!$pathCacheDisplay->loadTemplate('UrlPath' . $location->getType() , $handler['module']))
						$pathCacheDisplay->setDisplayTemplate('{# action #}/{# id #}/');

				$pathTemplate = $pathCacheDisplay->makeDisplay(false);
				$pathCache->storeData($pathTemplate);
			}
			$parameters = new DisplayMaker();
			$parameters->setDisplayTemplate($pathTemplate);
			$urlTags = $parameters->tagsUsed();

			foreach($urlTags as $tag)
			{
				$string = (isset($attributes[$tag])) ? $attributes[$tag] : '_';
				if(isset($attributes[$tag]))
				{
					$parameters->addContent($tag, htmlentities($attributes[$tag]));
					unset($attributes[$tag]);
				}else{
					$parameters->addContent($tag, '_');
				}
			}
			$urlString .= $parameters->makeDisplay(true);
			$urlString = rtrim(trim($urlString), '_/');
		}

		if(isset($attributes['ssl']))
		{
			if($attributes['ssl'] == true)
				$ssl = true;
			unset($attributes['ssl']);
		}elseif(isset($_SERVER['HTTPS'])){
			$ssl = true;
		}else{
			$ssl = false;
		}

		$site = (isset($location)) ? ModelRegistry::loadModel('Site', $location->getSite()) : ActiveSite::getSite();

		if($site)
		{
			$basePath = $site->getUrl($ssl);
		}else{

			$basePath = ($ssl) ? 'https://' : 'http://';
			$basePath .= $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0 ,
															strpos($_SERVER['PHP_SELF'], DISPATCHER));
		}

		$urlString = $basePath . $urlString;

		if(count($attributes) > 0)
		{
			$urlString .= '?';
			foreach($attributes as $name => $value)
				$urlString .= urlencode($name) . '=' . urlencode($value) . '&';
		}

		$urlString = rtrim(trim($urlString), '?& ');
		return $urlString;
	}

	/**
	 * This is one way to add attributes to the url
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function property($name, $value = false)
	{
		if($value !== false)
		{
			$this->attributes[$name] = $value;
			return $this;
		}else{
			return $this->attributes[$name];
		}

	}

	/**
	 * This returns an HtmlObject of the 'a' type
	 *
	 * @param string $text
	 * @return HtmlObject
	 */
	public function getLink($text)
	{
		$link = new HtmlObject('a');
		$link->property('href' , (string) $this);
		$link->wrapAround($text);
		return $link;
	}

	/**
	 * Sets attributes from an array
	 *
	 * @param array $attributes
	 */
	public function attributesFromArray($attributes)
	{
		foreach($attributes as $name => $value)
			$this->__set($name, $value);
	}

	public function __get($name)
	{
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}

	public function __set($name, $value)
	{
		if($value instanceof Location)
		{
			$name = 'locationId';
			$value = $value->getId();
		}

		if(!is_scalar($value))
			return false;

		if($name == 'id')
			$value = str_replace(' ', '-', $value);

		$this->attributes[$name] = $value;
	}

	public function __isset($name)
	{
		return isset($this->attributes[$name]);
	}

	public function __unset($name)
	{
		unset($this->attributes[$name]);
	}

	// for the sake of caching
	public function __sleep()
	{
		return array('attributes');
	}

	/**
	 * This function checks to see if a user has permission to access the resource the Url is pointing to
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function checkPermission($userId)
	{
		if(isset($this->attributes['locationId']))
		{
			$action = (isset($this->attributes['action'])) ? $this->attributes['action'] : 'Read';

			$location = new Location($this->attributes['locationId']);
			$resource = $location->getResource();
			$actionName = $resource->getAction($action);
			$requiredPermission = staticHack($actionName, 'requiredPermission');

			$permission = new Permissions($this->attributes['locationId'], $userId);
			return $permission->isAllowed($requiredPermission);

		}elseif(isset($this->attributes['module'])){

			$permissionList = new PermissionLists($userId);
			$actionName = importFromModule($this->attributes['action'], $this->attributes['module'], 'action');
			$permission = staticHack($actionName, 'requiredPermission');
			$permissionType = staticHack($actionName, 'requiredPermissionType');

			if(!$permission)
				$permission = 'execute';

			if(!$permissionType)
				$permissionType = 'base';

			if(!$permissionList->checkAction($permissionType, $permission))
			{
				return false;
			}
		}
		return true;
	}


}

?>