<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This object represents the location of an resource in the system
 *
 * @package System
 * @subpackage ModelSupport
 */
class Location
{
	/**
	 * This corresponds with the location id in the database
	 *
	 * @access protected
	 * @var int
	 */
	protected $id;

	/**
	 * This is the parent location id
	 *
	 * @access protected
	 * @var int
	 */
	protected $parent;

	/**
	 * This is the name of the object's saved location
	 *
	 * @access protected
	 * @var string
	 */
	protected $name;

	/**
	 * The resource type is used to find the resource that is saved at this location
	 *
	 * @access protected
	 * @var string
	 */
	protected $resourceType;

	/**
	 * This is a unique id assigned to a resource in order to assist in lookup
	 *
	 * @access protected
	 * @var int
	 */
	protected $resourceId;

	/**
	 * This is a timestamp representing the date the location was created
	 *
	 * @access protected
	 * @var int
	 */
	protected $createdOn;
	protected $publishDate;

	/**
	 * This is a timestampe representing the date the location was last saved
	 *
	 * @access protected
	 * @var ing
	 */
	protected $lastModified;

	/**
	 * This value contains meta data about the location
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * This value tells the location whether to inherit permissions and values from its parent
	 *
	 * @var bool
	 */
	protected $inherit = true;

	/**
	 * Different resources can populate this in different ways.
	 *
	 * @var string
	 */
	protected $status;

	/**
	 * This is the user id representing the owner of the location
	 *
	 * @access protected
	 * @var null|int
	 */
	protected $owner;

	/**
	 * This is the membergroup id representing the group that owns a location
	 *
	 * @access protected
	 * @var null|int
	 */
	protected $group;

	/**
	 * This is a list of names that locations can not use, because they have some special meaning to the system
	 *
	 * @access protected
	 * @var array
	 */
	protected $reservedNames = array('admin', 'modules', 'rest', 'users', 'system', 'resources', 'xml', 'json', 'html');

	/**
	 * If passed an int the constructor will load the information for that location
	 *
	 * @access public
	 * @param null|int $id
	 */
	public function __construct($id = null)
	{
		if(!is_null($id) && !is_numeric($id))
		{
			throw new TypeMismatch(array('integer', $id));
		}else{
			$this->loadLocation($id);
		}
	}

	/**
	 * This method places a resource at this location
	 *
	 * @param Model $object
	 */
	public function attachResource(Model $object)
	{
		$this->resourceType = $object->getType();
		$this->resourceId = $object->getId();
	}

	/**
	 * This method lets the developer manually attach a resource to a location, and is only recommended for special
	 * situations
	 *
	 * @param string $type
	 * @param int $id
	 */
	public function setResource($type, $id)
	{
		$this->resourceId = $id;
		$this->resourceType = $type;
	}

	/**
	 * This returns the current resource type
	 *
	 * @return false|string
	 */
	public function getType()
	{
		return isset($this->resourceType) ? $this->resourceType : false;
	}

	/**
	 * This method returns the model/resource that this location represents, or its resource type and id in an array
	 *
	 * @param bool $details
	 * @return Model|mixed|array
	 */
	public function getResource($details = false)
	{

		if($details)
		{
			return array('id' => $this->resourceId, 'type' => $this->resourceType);
		}else{
			$className = importModel($this->resourceType);
			$model = new $className($this->resourceId);
			return $model;
		}

	}

	/**
	 * Returns the timestampe of the time the location was first saved on
	 *
	 * @return int
	 */
	public function getCreationDate()
	{
		return $this->createdOn;
	}

	public function getPublishDate()
	{
		return $this->publishDate;
	}

	public function setPublishDate($date)
	{
		$this->publishDate = $date;
	}

	/**
	 * Returns the timestampe of the time the location was last modified
	 *
	 * @return int
	 */
	public function getLastModified()
	{
		return $this->lastModified;
	}

	/**
	 * Returns the parent location
	 *
	 * @return Location
	 */
	public function getParent()
	{
		if(is_numeric($this->parent))
			$this->parent = new Location($this->parent);

		if($this->parent instanceof Location)
		{
			return $this->parent;
		}else{
			return false;
		}
	}

	/**
	 * This function returns the location id, or false if the location hasn't been saved
	 *
	 * @return int|false
	 */
	public function getId()
	{
		return isset($this->id) ? $this->id : false;
	}

	/**
	 * This function returns the name, or false if it hasn't been set yet
	 *
	 * @return unknown
	 */
	public function getName()
	{
		return isset($this->name) ? $this->name : false;
	}

	/**
	 * This function returns the asked for meta setting, or the entire meta array
	 *
	 * @param null|string $name
	 * @return string|array
	 */
	public function getMeta($name = null)
	{
		if($name)
		{
			if(isset($this->meta[$name]))
			{
				return $this->meta[$name];
			}else{
				$parent = $this->getParent();
				if($parent = $this->getParent())
					return $parent->getMeta($name);
				return false;
			}
		}else{
			if($parent = $this->getParent())
			{
				return array_merge($parent->getMeta(), $this->meta);
			}else{
				return $this->meta;
			}
		}
	}

	/**
	 * This function sets a meta value
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setMeta($name, $value)
	{
		$this->meta[$name] = $value;
	}

	/**
	 * This function is used to set the status of the location. Generally it should be called through its attached
	 * resource to allow any filtering or other custom status handling to take place, although it can certainly be used
	 * outside that context should the need arise.
	 *
	 * @param string $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * This function is used to retrieve the status of the location. If the location is new and does not yet have a
	 * status it will return false.
	 *
	 * @return string
	 */
	public function getStatus()
	{
		if(!isset($this->status))
			return false;
		return $this->status;
	}

	/**
	 * This allows you to change the owner
	 *
	 * @param User $user
	 */
	public function setOwner($user)
	{
		if(!($user instanceof Model && $user->getType() == 'User'))
			throw new TypeMismatch(array('User', $user));

		$this->owner = $user->getId();
	}

	/**
	 * This returns the current owner, or false is one isn't set
	 *
	 * @return User|false
	 */
	public function getOwner()
	{
		if(isset($this->owner))
			return ModelRegistry::loadModel('User', $this->owner);

		return false;
	}

	/**
	 * This allows you to set the owning group of the object
	 *
	 * @param MemberGroup $memberGroup
	 */
	public function setOwnerGroup(MemberGroup $memberGroup)
	{
		$this->group = $memberGroup->getId();
	}

	/**
	 * This returns the group that owns the location, or false if one isn't set
	 *
	 * @return MemberGroup|false
	 */
	public function getOwnerGroup()
	{
		if(isset($this->group))
			return new MemberGroup($this->group);

		return false;
	}

	/**
	 * Tells you if permissions are inherited or not
	 *
	 * @return bool
	 */
	public function inheritsPermission()
	{
		return ($this->inherits == 1);
	}

	/**
	 * Enable or disable parental inheritence
	 *
	 * @param bool $on
	 */
	public function setInherit($on = true)
	{
		$this->inherits = $on ? 1 : 0;
	}

	/**
	 * Set the name for the location
	 *
	 * @param string $name
	 */
	public function setName($name)
	{
		if(in_array(strtolower($name), $this->reservedNames))
			throw new LocationError('Attempted to name location a reserved name: ' . $name);


		$this->name = str_replace(' ', '_', $name);
	}

	/**
	 * Set the parent location
	 *
	 * @param Location $parent
	 */
	public function setParent(Location $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * This function loads the location information from the database, or cache
	 *
	 * @access protected
	 * @cache locations *id info
	 * @param int $id
	 * @return bool
	 */
	protected function loadLocation($id = null)
	{
		if($id != null)
		{
			if(!is_numeric($id))
				throw new TypeMismatch(array('integer', $id));

			$cache = new Cache('locations', $id, 'info');
			$locationInfo = $cache->getData();

			if($cache->isStale())
			{
				$dbLocation = new ObjectRelationshipMapper('locations');
				$dbLocation->location_id = $id;
				if($dbLocation->select(1))
				{
					$locationInfo['id'] = $dbLocation->location_id;
					$locationInfo['parent'] = $dbLocation->parent;
					$locationInfo['name'] = $dbLocation->name;
					$locationInfo['resourceType'] = $dbLocation->resourceType;
					$locationInfo['resourceId'] = $dbLocation->resourceId;
					$locationInfo['createdOn'] = strtotime($dbLocation->creationDate . 'UTC');
					$locationInfo['lastModified'] = strtotime($dbLocation->lastModified . 'UTC');
					$locationInfo['publishDate'] = strtotime($dbLocation->publishDate . 'UTC');
					$locationInfo['owner'] = $dbLocation->owner;
					$locationInfo['group'] = $dbLocation->groupOwner;
					$locationInfo['inherits'] = ($dbLocation->inherits == 1);
					$locationInfo['status'] = $dbLocation->resourceStatus;

					$db_meta = new ObjectRelationshipMapper('locationMeta');
					$db_meta->location_id = $locationInfo['id'];
					$meta = array();

					if($db_meta->select() && $results = $db_meta->resultsToArray())
						foreach($results as $row)
							$meta[$row['name']] = $row['value'];

					$locationInfo['meta'] = $meta;

				}else{
					$locationInfo = false;
				}
				$cache->storeData($locationInfo);
			}

			if($locationInfo === false)
				return false;

			$properties = array_keys($locationInfo);
			foreach($properties as $property)
				$this->$property = $locationInfo[$property];

			return true;
		}
	}

	/**
	 * This method saves the location to the database
	 *
	 * @return bool
	 */
	public function save()
	{
		$db_location = new ObjectRelationshipMapper('locations');

		if($this->id > 0)
		{
			$db_location->location_id = $this->id ;
			$db_location->select('1'); // fill the object with the saved values

		}else{
			// should only run when id isn't set (so new objects only)
			$db_location->creationDate = gmdate('Y-m-d H:i:s');
		}
		$db_location->lastModified = gmdate('Y-m-d H:i:s');
		$db_location->publishDate = gmdate('Y-m-d H:i:s', $this->publishDate);

		if($parent = $this->getParent())
		{
			$parentId = $parent->getId();

			if(is_numeric($parentId))
				$db_location->parent = $parentId;
		}


		$db_location->name = $this->name;
		$db_location->resourceType = $this->resourceType;
		$db_location->resourceId = $this->resourceId;
		$db_location->inherits = ($this->inherit) ? 1 : 0;

		if(isset($this->status))
			$db_location->resourceStatus = $this->status;

		if(is_numeric($this->owner))
		{
			$db_location->owner = $this->owner;
		}else{
			$db_location->querySet('owner', 'NULL');
		}

		if(is_numeric($this->group))
		{
			$db_location->groupOwner = $this->group;
		}elseif($parent && $parentGroup = $parent->getOwnerGroup()){
			$groupId = $parentGroup->getId();
			$db_location->groupOwner = $groupId;
			$this->group = $groupId;
		}else{
			$db_location->querySet('groupOwner', 'NULL');
		}

		if(!$db_location->save())
		{
 			throw new LocationError('Unable to save location due to error ' . $db_location->errorString);
		}

		if(!is_numeric($this->id))
			$this->id = $db_location->location_id;

		// Erase all current values, since they are going to be replaced anyways
		$metaDelete = new ObjectRelationshipMapper('locationMeta');
		$metaDelete->location_id = $this->id;
		$metaDelete->delete(0);

		foreach($this->meta as $name => $value)
		{
			$metaAdd = new ObjectRelationshipMapper('locationMeta');
			$metaAdd->location_id = $this->id;
			$metaAdd->name = $name;
			$metaAdd->value = $value;
			$metaAdd->save();
		}

		Cache::clear('locations', $this->id);
		return true;
	}

	/**
	 * This method returns the child location with the specified name, or false if it doesn't exist
	 *
	 * @cache locations *id child *name
	 * @param string $name
	 * @return Location|false
	 */
	public function getChildByName($name)
	{
		$cache = new Cache('locations', $this->id, 'children', $name);
		$childId = $cache->getData();
		if($cache->isStale())
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT location_id FROM locations WHERE parent = ? AND name = ?');
			$stmt->bindAndExecute('is', $this->getId(), $name);

			if($childLocation = $stmt->fetch_array())
			{
				$childId = $childLocation['location_id'];
			}else{
				$childId = false;
			}

			$cache->storeData($childId);
		}

		return (is_int($childId)) ? new Location($childId) : false;
	}

	/**
	 * Returns the children locations as an array
	 *
	 * @cache locations *id children *type
	 * @param string $type This value can limit the results to a specific resource type
	 * @return array|false
	 */
	public function getChildren($type = 'all')
	{
		$cache = new Cache('locations', $this->id, 'children', $type);

		if(!$childrenIds = $cache->getData())
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();

			if($type != 'all')
			{
				$stmt->prepare('SELECT location_id FROM locations WHERE parent = ? AND resourceType = ?');
				$stmt->bindAndExecute('is', $this->id, $type);
			}else{
				$stmt->prepare('SELECT location_id FROM locations WHERE parent = ?');
				$stmt->bindAndExecute('i', $this->id);
			}

			while($childLocation = $stmt->fetch_array())
				$childrenIds[] = $childLocation['location_id'];

			$cache->storeData($childrenIds);
		}

		if(!$childrenIds || count($childrenIds) < 1)
			return false;

		foreach($childrenIds as $id)
			$locations[] = new Location($id);

		return $locations;
	}

	/**
	 * This function checks to see if the location has any children.
	 *
	 * @cache locations *id hasChildren
	 * @return bool
	 */
	public function hasChildren()
	{
		$id = $this->getId();

		if(!is_numeric($id))
			return false;

		$cache = new Cache('locations', $id, 'hasChildren');
		$hasChildren = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT location_id FROM locations WHERE parent = ? LIMIT 1');
			$stmt->bindAndExecute('i', $id);
			$hasChildren = ($stmt->num_rows() == 1);
			$cache->storeData($hasChildren);
		}
		return $hasChildren;
	}

	/**
	 * Magic method to convert location into string
	 *
	 * @return string
	 */
	public function __toString()
	{
		if($parent = $this->getParent())
		{
			$output = (string) $parent;
		}

		if($this->name == 'root'){
			return '/';
		}

		$output .= $this->name;

		if($this->resource == 'directory' || $this->resource == 'site')
			$output .= '/';

		return $output;
	}

	/**
	 * Returns the site id this location descends from
	 *
	 * @return int|false
	 */
	public function getSite()
	{
		if($this->getType() == 'Site')
		{
			$tempResource = $this->getResource(true);
			return $tempResource['id'];
		}

		if($parent = $this->getParent())
			return $parent->getSite();

		return false;
	}

	/**
	 * This function returns an array with the path from root to the current option. Running this on an unsaved location
	 * will result in an exception.
	 *
	 * @return array Integers, representing location ids
	 */
	public function getPathToRoot()
	{
		if(!($id = $this->getId()))
			throw new CoreError('Unable to get path for unsaved location.');

		$path = ($parentLocation = $this->getParent()) ? $parentLocation->getPathToRoot() : array();
		$path[] = $id;

		return $path;
	}

	/**
	 * Returns a multidemensional tree of locations
	 *
	 * @param string $types
	 * @param bool $isFirst
	 * @return array
	 */
	public function getTreeArray($types = '', $isFirst = true)
	{
		if(is_string($types) && strlen($types) > 0)
		{
			$types = array($types);
		}elseif(!is_array($types)){
			$types = array();
		}

		if(in_array('directory', $types))
			$types = array_merge($types, $this->directoryTypes);


		$outputArray = array();
		if((count($types) < 1 || in_array($this->resource, $types)) && $this->name != 'root')// && !(in_array($this->resource, $this->directoryTypes) && !$listDirectory))
		{
			$outputArray[$this->id] = (string) $this;
		}


		if(count($type) > 0)
		{
			if(!in_array('directory', $types))
				$types = array_merge($types, $this->directoryTypes);

			$children = array();
			foreach($types as $type)
			{
				$children = array_merge($children, $this->getChildren($type));
			}
		}else{
			$children = $this->getChildren();
		}

		if(is_array($children))
		{
			foreach($children as $child)
			{
				$childTree = $child->getTreeArray($types, false);

				foreach($childTree as $childId => $childName)
				{
					$outputArray[$childId] = $childName;
				}
			}
		}

		if($isFirst)
			asort($outputArray);

		return $outputArray;

	}
}

class LocationError extends CoreError {}
?>