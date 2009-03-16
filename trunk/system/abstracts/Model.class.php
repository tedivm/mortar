<?php

class AbstractModel implements Model
{
	static public $type;

	protected $table;

	protected $id;
	protected $location;
	protected $package;

	protected $attributes;
	protected $properties;


	public function __construct($id = null)
	{
		if(!is_null($id))
			$this->load($id);
	}

	public function save()
	{
		if(!$this->location)
		{
			$location = new Location();
			$location->setName($this->attributes['name']);
			$location->setResource('new', 0);
			$location->save();
		}

		if(isset($this->table))
		{
			$record = new ObjectRelationshipMapper($this->table);

			if(isset($this->id))
				$record->primaryKey = $this->id;

			$columns = $record->getColumns(false, false);

			foreach($columns as $columnName)
				$record->$columnName = $this->properties[$columnName];


			if($record->save())
			{
				if(!isset($this->id))
					$this->id = $record->primaryKey;

				$location = $this->getLocation();
				$location->attachResource($this);
				$location->save();

				return $location;
			}else{
				return false;
			}
		}

		return false;
	}

	public function delete()
	{
		if(isset($this->table))
		{
			$record = new ObjectRelationshipMapper($this->table);
			$record->primaryKey($id);

			if($record->select(1))
			{
				$record->delete(1);
			}else{

			}

			$location = $this->getLocation();
			$location->delete();
		}
	}

	public function checkAuth($action, $user = null)
	{
		if(!$user)
			$user = ActiveUser::getInstance();

		$permission = new Permissions($this->location, 'user');
		return $permission->isAllowed($action, staticHack($this->model, 'type'));
	}

	public function getAction($actionName)
	{
		$packageInfo = new PackageInfo($this->package);

		$moduleActionName = self::$type . $actionName;

		$actionInfo = $packageInfo->getActions($moduleActionName);

		if(!$actionInfo)
		{
			if(in_array($actionName, array('Read', 'Add', 'Edit', 'Delete'))
				|| ($actionName == 'Execute' && method_exists($this, 'execute')) )
			{
				$actionInfo['className'] = 'ModelAction' . $actionName;
				$config = Config::getInstance();
				$actionInfo['path'] = $config['path']['mainclasses'] . 'models/actions/' . $actionName . '.class.php';
			}else{
				return false;
			}
		}

		return $actionInfo;
	}

	public function getAttributes()
	{
		return $this->attributes;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getLocation()
	{
		if(!isset($this->location))
		{
			$location = new Location();
			$location->setName($this->attributes['name']);
			$location->setResource('new', 0);
			$this->location = $location;
		}

		return $this->location;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function getType()
	{
		return self::$type;
	}


	protected function load($id)
	{
		if(isset($this->table))
		{
			$cache = new Cache('models', $this->getType(), $id, 'info');

			$info = $cache->getData();

			if(!$cache->cacheReturned)
			{
				$record = new ObjectRelationshipMapper($this->table);
				$record->primaryKey($id);

				if($record->select(1))
				{
					$info['id'] = $id;
					$columns = $record->getColumns(false, false);

					foreach($columns as $columnName)
					{
						$info['properties'][$columnName] = $record->$columnName;
					}

					$db = DatabaseConnection::getConnection('default_read_only');
					$stmt = $db->stmt_init();
					$stmt->prepare('SELECT location_id FROM locations WHERE resourceType = ? AND resourceId = ? LIMIT 1');

					$stmt->bindAndExecute('si', $this->getType(), $this->getId());

					if($stmt->num_rows > 0)
					{
						$locationArray = $stmt->fetch_array();
						$info['locationId'] = $locationArray['location_id'];
					}
				}

				$cache->storeData($info);
			}

			$this->id = $info['id'];
			$this->properties = $info['properties'];
			$this->location = new Location($info['locationId']);



		}else{

		}

	}


	// array functions define attributes and meta data
	public function offsetGet($offset)
	{
		return $this->properties[$offset];
	}

	public function offsetSet($offset, $value)
	{
		if(!is_string($value))
			throw new BentoError('Model attributes must be strings.');

		return $this->properties[$offset] = $value;
	}

	public function offsetExists($offset)
	{
		return isset($this->properties[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->properties[$offset]);
	}


	// class properties define content and the actual substance of the class
	public function __get($name)
	{
		return $this->content[$name];
	}

	public function __set($name, $value)
	{
		return $this->content[$name] = $value;
	}

	public function __isset($name)
	{
		return isset($this->content[$name]);
	}

	public function __unset($name)
	{
		unset($this->content[$name]);
	}

	public function __toArray()
	{
		$finalArray = array();

		foreach($this->properties as $index => $property)
		{
			if($property->isPublic())
			{
				$propertyName = $property->getName();
				$node = $this->$name;

				if(is_scalar($node))
				{
					$finalArray[$name] = $node;
				}elseif(is_array()){

					// do we need to descend and sanitize this thing?
					$finalArray[$name] = $node;

				}elseif($node instanceof Model){
					$finalArray[$name] = $node->__toArray();
				}
			}
		}
		return $finalArray;
	}


}



?>