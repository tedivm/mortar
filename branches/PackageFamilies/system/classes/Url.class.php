<?php
/**
 * Mortar
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

	static $resourceMaps = array('users' => 'User', 'groups' => 'MemberGroup');

	static public function getSpecialPaths($full = false)
	{
		return $full ? self::$resourceMaps : array_keys(self::$resourceMaps);
	}

	static public function getModelFromShortcut($shortcut)
	{
		return isset(self::$resourceMaps[$shortcut]) ? self::$resourceMaps[$shortcut] : false;
	}

	static public function getShortcutFromModel($shortcut)
	{
		$searchArray = array_flip(self::$resourceMaps);
		return isset($searchArray[$shortcut]) ? $searchArray[$shortcut] : false;
	}

	/**
	 * This function returns a string, the Url
	 *
	 * @cache *type *odule url pathCache
	 * @return string
	 */
	public function __toString()
	{
		$attributes = $this->attributes;

		if(( isset($attributes['format']) && $attributes['format'] == 'html')
			&& (!isset($attributes['ioHandler']) || $attributes['ioHandler'] == 'http'))
		{
			unset($attributes['format']);
		}

		return UrlWriter::getUrl($attributes);
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
		$name = strtolower($name);

		if($name == 'location')
			$name = 'locationId';

		if($name == 'module' && !is_numeric($value))
		{
			if($value instanceof PackageInfo)
			{
				$value = $value->getId();
			}else{
				throw new UrlError('Module attribute must be an integer or PackageInfo object');
			}
		}

		if($value instanceof Location)
			$value = $value->getId();

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

	/**
	 * This function allows instances of the class to be serialized. This is primarily for the purposes of caching.
	 *
	 * @return array This returns the properties that should be saved in the serialization.
	 */
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
		try
		{
			if(isset($this->attributes['locationId']))
			{
				if(isset($this->attributes['action'])
					&& $this->attributes['action'] == 'Add'
					&& isset($this->attributes['type']))
				{
					$resource = ModelRegistry::loadModel($this->attributes['type']);
					$actionInfo = $resource->getAction('Add');
				}else{
					$action = (isset($this->attributes['action'])) ? $this->attributes['action'] : 'Read';
					$location = Location::getLocation($this->attributes['locationId']);
					$resource = $location->getResource();
					$actionInfo = $resource->getAction($action);
				}

				$actionName = $actionInfo['className'];
				$requiredPermission = staticHack($actionName, 'requiredPermission');
				$permissions = new Permissions($this->attributes['locationId'], $userId);
				return $permissions->isAllowed($requiredPermission);


			}elseif(isset($this->attributes['type'])){

				$action = (isset($this->attributes['action'])) ? $this->attributes['action'] : 'Read';
				$resource = ModelRegistry::loadModel($this->attributes['type']);
				$actionInfo = $resource->getAction($action);
				$requiredPermission = staticHack($actionInfo['className'], 'requiredPermission');
				return $resource->checkAuth($requiredPermission);

			}elseif(isset($this->attributes['module'])){

				$permissionsList = new PermissionLists($userId);
				$actionName = importFromModule($this->attributes['action'], $this->attributes['module'], 'action');
				$permission = staticHack($actionName, 'requiredPermission');
				$permissionType = staticHack($actionName, 'requiredPermissionType');

				if(!$permission)
					$permission = 'execute';

				if(!$permissionType)
					$permissionType = 'base';

				if(!$permissionsList->checkAction($permissionType, $permission))
				{
					return false;
				}
			}
			return true;
		}catch(Exception $e){
			return false;
		}
	}

}

class UrlError extends CoreError();
?>