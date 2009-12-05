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
	 * This is an array of strings that, when used in the path as the first value, refers to a model type (with the id
	 * being the second path variable). This is primarily used for models that don't have an associated location, such
	 * as Users.
	 *
	 * @var array
	 */
	static public $specialDirectories = array('Users' => 'User');

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
		if(strtolower($name) == 'location')
			$name = 'locationId';

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
				$location = new Location($this->attributes['locationId']);
				$resource = $location->getResource();
				$actionInfo = $resource->getAction($action);
			}

			$actionName = $actionInfo['className'];
			$requiredPermission = staticHack($actionName, 'requiredPermission');
			$permissions = new Permissions($this->attributes['locationId'], $userId);
			return $permissions->isAllowed($requiredPermission);

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
	}


}

?>