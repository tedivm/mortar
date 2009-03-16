<?php

interface intlocation
{
	public function parent_location();

	public function location_id();

	public function resource_type();
}

/*
interface Location
{
	public function __construct($id = null);

	public function getId();

	public function parent($id = null);

	public function name($name = null);

	public function resource();

	public function resourceId();

	public function setResource($model);

	public function getCreationDate();

	public function getLastModified();

	public function owner();

	public function save();

	public function delete();

}

*/

class Location
{
	protected $id;
	protected $parent;
	protected $name;
	protected $resourceType;
	protected $resourceId;
	protected $createdOn;
	protected $lastModified;
	protected $meta = array();
	protected $inherit = true;

	public function __construct($id = '')
	{
		$this->loadLocation($id);
	}

	public function attachResource(Model $object)
	{
		$this->resourceType = $object->getType();
		$this->resourceId = $object->getId();
	}

	public function setResource($type, $id)
	{
		$this->resourceId = $id;
		$this->resourceType = $type;
	}

	public function getResource($id = false)
	{
		if($id)
		{
			return array('id' => $this->resourceId, 'type' => $this->resourceType);
		}else{
			$modelInfo = ModelRegistry::getHandler($this->resourceType);
			$model = new $modelInfo['class']($this->resourceId);
			return $model;
		}

	}

	public function getCreationDate()
	{
		return $this->createdOn;
	}

	public function getLastModified()
	{
		return $this->lastModified;
	}

	public function getParent()
	{
		return new Location($this->parent);
		return $this->parent;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getMeta($name = null)
	{
		if($name)
		{
			return $this->meta[$name];
		}else{
			return $this->meta;
		}
	}

	public function setMeta($name, $value)
	{
		$this->meta[$name] = $value;
	}

	public function getLink()
	{
		 switch(strtolower($this->resourceType))
		 {
		 	case 'site':

		 		break;

		 	case 'root':

		 		break;

		 	default:

		 		$url = $this->parent->getLink();
		 		$url .= $this->getName() . '/';






		 		break;
		 }
	}


	public function inheritsPermission()
	{
		return ($this->inherits == 1);
	}

	public function setName($name)
	{
		$this->name = str_replace(' ', '_', $name);
	}

	public function setParent(Location $parent = null)
	{
		$this->parent = $location;
	}

	protected function loadLocation($id = null)
	{
		if($id != null)
		{
			$cache = new Cache('locations', $id, 'info');
			$locationInfo = $cache->getData();

			if(!$cache->cacheReturned)
			{
				$dbLocation = new ObjectRelationshipMapper('locations');
				$dbLocation->location_id = $id;
				if($dbLocation->select('1'))
				{
					$locationInfo['id'] = $dbLocation->id;
					$locationInfo['parent'] = $dbLocation->parent;
					$locationInfo['name'] = $dbLocation->name;
					$locationInfo['resourceType'] = $dbLocation->resourceType;
					$locationInfo['resourceId'] = $dbLocation->resourceId;
					$locationInfo['createdOn'] = $dbLocation->createdOn;
					$locationInfo['lastModified'] = $dbLocation->lastModified;
					$locationInfo['inherits'] = ($dbLocation->inherits == 1);

					$db_meta = new ObjectRelationshipMapper('location_meta');
					$db_meta->location_id = $locationInfo['id'];
					$meta = array();
					if($db_meta->select())
					{
						$results = $db_meta->resultsToArray();
						foreach($results as $row)
						{
							$meta[$row['name']] = $row['value'];
						}
					}
					$locationInfo['meta'] = $meta;
				}else{
					$locationInfo = false;
				}

				$cache->store_data($locationInfo);
			}

			if($locationInfo != false)
			{
				$properties = array_keys($locationInfo);

				foreach($properties as $property)
					$this->$property = $locationInfo[$property];

				if(is_numeric($this->parent))
				{
					$this->parent = new Location($this->parent);
					$this->meta = array_merge($this->parent->getMeta(), $this->meta);
				}

				return true;
			}else{
				return false;
			}
		}
	}


	public function save()
	{
		$db_location = new ObjectRelationshipMapper('locations');

		if($this->id > 0)
		{

			$db_location->location_id = $this->id ;
			$db_location->select('1'); // fill the object with the saved values
		}else{
			// should only run when id isn't set (so new objects only)
			$db_location->query_set('creationDate', 'NOW()');
		}

		$db_location->query_set('lastModified', 'NOW()');

		if(($this->parent instanceof Location))
		{
			$parentId = $this->parent->getId();

			if(is_numeric($parentId))
				$db_location->parent = $parentId;
		}


		$db_location->name = $this->name;
		$db_location->resourceType = $this->resourceType;
		$db_location->resourceId = $this->resourceId;
		$db_location->inherit = ($this->inherits) ? 1 : 0;


		if(!$db_location->save())
		{
 			throw new BentoError('Unable to save location due to error ' . $db_location->sql_errno);
		}


		if(!is_numeric($this->id))
			$this->id = $db_location->location_id;


		// Erase all current values, since they are going to be replaced anyways
		$metaDelete = new ObjectRelationshipMapper('location_meta');
		$metaDelete->location_id = $this->id;
		$metaDelete->delete(0);


		foreach($this->meta as $name => $value)
		{
			$metaAdd = new ObjectRelationshipMapper('location_meta');
			$metaAdd->location_id = $this->id;
			$metaAdd->name = $name;
			$metaAdd->value = $value;
			$metaAdd->save();
		}

		Cache::clear('locations', $this->id, 'info');
	}





	public function getChildByName($name)
	{
		$cache = new Cache('locations', $this->id, 'child', $name);
		$childId = $cache->get_data();
		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');

			$stmt = $db->stmt_init();


			$stmt->prepare('SELECT location_id FROM locations WHERE parent = ? AND name = ?');
			$stmt->bind_param_and_execute('is', $this->id, $name);

			$childLocation = $stmt->fetch_array();

			$childId = $childLocation['location_id'];

			$cache->store_data($childId);
		}

		if(is_int($childId))
		{
			return new Location($childId);
		}else{
			return false;
		}

	}

	public function getChildren($type = 'all')
	{

		$cache = new Cache('locations', $this->id, 'children', $type);

		if(!$childrenIds = $cache->get_data())
		{
			$db = db_connect('default_read_only');

			$stmt = $db->stmt_init();

			if($type != 'all')
			{
				$stmt->prepare('SELECT location_id FROM locations WHERE parent = ? AND resource = ?');
				$stmt->bind_param_and_execute('is', $this->id, $type);
			}else{
				$stmt->prepare('SELECT location_id FROM locations WHERE parent = ?');
				$stmt->bind_param_and_execute('i', $this->id);
			}


			while($childLocation = $stmt->fetch_array())
			{
				$childrenIds[] = $childLocation['location_id'];

			}


			$cache->store_data($childrenIds);

		}


		if(!$childrenIds || count($childrenIds) < 1)
		{
			return false;
		}

		foreach($childrenIds as $id)
		{
			$locations[] = new Location($id);
		}

		return $locations;

	}

	public function meta($name)
	{
		return (isset($this->meta[$name])) ? $this->meta[$name] : false;
	}

	public function __toString()
	{
		if(isset($this->parent))
		{
			$output = (string) $this->parent;
		}

		if($this->name == 'root'){
			return '/';
		}

		$output .= $this->name;

		if($this->resource == 'directory' || $this->resource == 'site')
			$output .= '/';

		return $output;
	}

	public function getSite()
	{
		if($this->resource == 'site'){
			$siteObject = new ObjectRelationshipMapper('sites');
			$siteObject->location_id = $this->id;
			$siteObject->select(1);

			if($siteObject->total_rows() > 0)
				$site = $siteObject->site_id;
			else
				$site = false;

		}elseif($this->parent instanceof Location){
			$site = $this->parent->getSite();
		}

		return $site;
	}

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
?>