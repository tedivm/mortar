<?php

interface intlocation
{
	public function parent_location();

	public function location_id();

	public function resource_type();
}

class Location implements intlocation
{
	public $id;
	public $parent;
	public $name;
	public $resource;
	public $inherits = true;
	public $meta = array();
	public $siteId;
	protected $directoryTypes = array('directory', 'site');
	protected $createdOn;

	public function __construct($id = '')
	{
		$this->loadLocation($id);
	}

	protected function loadLocation($id)
	{
		if($id != '')
		{

			$cache = new Cache('locations', $id, 'info');
			!$locationInfo = $cache->get_data();

			if(!$cache->cacheReturned)
			{
				$db_location = new ObjectRelationshipMapper('locations');
				$db_location->location_id = $id;
				if($db_location->select('1'))
				{
					$locationInfo['parentId'] = $db_location->location_parent;
					$locationInfo['id'] = $db_location->location_id;
					$locationInfo['resource'] = $db_location->location_resource;
					$locationInfo['name'] = $db_location->location_name;
					$locationInfo['inherits'] = ($db_location->inherit == 1);
					$locationInfo['siteId'] = $this->getSite();
					$locationInfo['createdOn'] = $db_location->location_createdOn;

					$db_meta = new ObjectRelationshipMapper('location_meta');
					$db_meta->location_id = $locationInfo['id'];
					if($db_meta->select())
					{
						$results = $db_meta->resultsToArray();
						foreach($results as $row)
						{
							$meta[$row['name']] = $row['value'];
						}
						$locationInfo['meta'] = $meta;
					}
				}else{
					$locationInfo = false;
				}
				$cache->store_data($locationInfo);
			}

			if($locationInfo != false)
			{
				$this->id = $locationInfo['id'];
				$this->parent = ($locationInfo['parentId'] > 0) ? new Location($locationInfo['parentId']) : false;
				$this->siteId = $locationInfo['siteId'];
				$this->resource = $locationInfo['resource'];
				$this->name = $locationInfo['name'];
				$this->inherits = $locationInfo['inherits'];
				$this->meta = $locationInfo['meta'];
				$this->createdOn = $locationInfo['createdOn'];
				return true;
			}else{
				return false;
			}
		}
	}

	public function parent_location()
	{
		return $this->parent;
	}

	public function getParent()
	{
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

	public function getResource()
	{
		return $this->resource;
	}

	public function getCreationDate()
	{
		return $this->createdOn;
	}

	//need to get rid of that
	public function location_id()
	{
		return $this->id;
	}

	public function resource_type()
	{
		return $this->resource;
	}

	public function save()
	{
		$db_location = new ObjectRelationshipMapper('locations');

		if($this->id > 0)
		{
			$db_location->location_id = $this->id ;
			$db_location->select('1');
		}else{
			$db_location->query_set('location_createdOn', 'NOW()');
		}


		if(($this->parent instanceof Location))
		{
			$parentId = $this->parent->location_id();

			if(is_numeric($parentId))
			{
				$db_location->location_parent = $parentId;
			}

		}elseif(is_numeric($this->parent) && $this->parent > 0){
			$db_location->location_parent = $this->parent;
		}


		$db_location->location_name = str_replace('_', ' ', $this->name);
		$db_location->location_resource = $this->resource;

		$db_location->inherit = ($this->inherits) ? 1 : 0;

		if(!$db_location->save())
		{
 			throw new BentoError('Unable to save location');
		}


		$this->id = $db_location->location_id;


		$metaDelete = new ObjectRelationshipMapper('location_meta');
		$metaDelete->location_id = $this->id;
		$metaDelete->delete(0);

		if(is_array($this->meta))
		{
			foreach($this->meta as $name => $value)
			{
				$metaAdd = new ObjectRelationshipMapper('location_meta');
				$metaAdd->location_id = $this->id;
				$metaAdd->name = $name;
				$metaAdd->value = $value;
				$metaAdd->save();
			}
		}
		Cache::clear('locations', $this->id, 'info');
	}

	public function inheritsPermission()
	{
		return ($this->inherits == 1);
	}

	public function getChildByName($name)
	{
		$cache = new Cache('locations', $this->id, 'child', $name);
		$childId = $cache->get_data();
		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');

			$stmt = $db->stmt_init();


			$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ? AND location_name = ?');
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
				$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ? AND location_resource = ?');
				$stmt->bind_param_and_execute('is', $this->id, $type);
			}else{
				$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ?');
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