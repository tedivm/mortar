<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 *
 *
 * @package System
 * @subpackage ModelSupport
 */
class AbstractModel implements Model
{
	static public $type;

	protected $table;

	protected $id;
	protected $location;
	protected $module;

	protected $properties;
	protected $content;

	public function __construct($id = null)
	{
		$handlerInfo = ModelRegistry::getHandler($this->getType());
		$this->module = $handlerInfo['module'];

		if(!is_null($id))
		{
			$cache = new Cache('models', $this->getType(), $id, 'location');
			$locationId = $cache->getData();

			if(!$cache->cacheReturned)
			{
				$db = DatabaseConnection::getConnection('default_read_only');
				$stmt = $db->stmt_init();
				$stmt->prepare('SELECT location_id FROM locations WHERE resourceType = ? AND resourceId = ? LIMIT 1');
				$stmt->bindAndExecute('si', $this->getType(), $id);

				if($stmt->num_rows > 0)
				{
					$locationArray = $stmt->fetch_array();
					$locationId = $locationArray['location_id'];
					$cache->storeData($locationId);
				}else{
					$locationId = false;
				}
			}

			if($locationId)
				$this->location = new Location($locationId);

			$this->load($id);
		}

	}

	public function save($parent = null)
	{
		$db = DatabaseConnection::getConnection('default');
		$db->autocommit(false);

		try
		{
			if(!$this->location)
			{
				if(!$parent)
					throw new BentoError('On creation a parent location is required.');
				$this->location = new Location();
			}

			if($parent)
			{
				if(!($parent instanceof Location))
					throw new TypeMismatch(array('Location', $parent));

				if(!$this->canSaveTo($parent->getType()))
					throw new BentoError('Unable to save to resource type ' . $parent->getType());

				$this->location->setParent($parent);
			}


			if(isset($this->table))
			{
				$record = new ObjectRelationshipMapper($this->table);

				if(isset($this->id))
					$record->primaryKey = $this->id;

				$columns = $record->getColumns(false, false);

				foreach($columns as $columnName)
				{
					if(isset($this->content[$columnName]))
						$record->$columnName = $this->content[$columnName];
				}


				if(!$record->save())
					throw new BentoError('Unable to save model information to table');

				if(!isset($this->id))
					$this->id = $record->primaryKey;

			}

			$this->location->setResource($this->getType(), $this->getId());
			$this->location->setName($this->properties['name']);

			if(!$this->location->save())
				throw new BentoError('Unable to save model location');


		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}

		$db->autocommit(true);
		return true;
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

		$permission = new Permissions($this->location, $user);
		return $permission->isAllowed($action, staticHack($this->model, 'type'));
	}

	public function getAction($actionName)
	{
		$packageInfo = new PackageInfo($this->module);
		$moduleActionName = $this->getType() . $actionName;
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

	public function getContent()
	{
		return $this->content;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getLocation()
	{
		if(!isset($this->location))
		{
			$this->location = new Location();
			$id = (isset($this->id)) ? $this->id : 0;
			$name = (isset($this->properties['name'])) ? $this->properties['name'] : 'tmp';
			$this->location->setResource($this->getType(), $id);
			$this->location->setName($name);
		}
		return $this->location;
	}

	public function getProperties()
	{
		return $this->properties;
	}

	public function getType()
	{
		return staticHack(get_class($this), 'type');
	}

	public function getModule()
	{
		return $this->module;
	}

	public function setParent(Location $parent)
	{
		if(!$this->canSaveTo($parent->getType()))
			throw new BentoError('Attempted to save ' . $this->getType() . ' to incompatible parent location.', 409);

		$location = $this->getLocation();
		$location->setParent($parent);
	}

	public function canSaveTo($resourceType)
	{
		if(in_array($resourceType, $this->allowedParents))
			return true;

		return false;
	}

	protected function load($id)
	{
		$cache = new Cache('models', $this->getType(), $id, 'info');
		$info = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$modelInfo = ModelRegistry::getHandler($this->getType());
			$info['module'] = $modelInfo['module'];

			if(isset($this->table))
			{
				$record = new ObjectRelationshipMapper($this->table);
				$record->primaryKey = $id;

				if($record->select(1))
				{

					$info['id'] = $id;
					$columns = $record->getColumns(false, false);

					foreach($columns as $columnName)
					{
						$info['content'][$columnName] = $record->$columnName;
					}
				}
			}
			$cache->storeData($info);
		}

		if(isset($info['id']))
			$this->id = $info['id'];

		if(isset($info['module']))
			$this->module = $info['module'];

		if(isset($info['content']))
			$this->content = $info['content'];

		return true;
	}


	// class properties define attributes and meta data
	public function __get($offset)
	{
		if(isset($this->properties[$offset]))
			return $this->properties[$offset];
	}

	public function __set($offset, $value)
	{
		if(!is_scalar($value))
			throw new BentoError('Model attributes must be scalar.');

		return $this->properties[$offset] = $value;
	}

	public function __isset($offset)
	{
		return isset($this->properties[$offset]);
	}

	public function __unset($offset)
	{
		unset($this->properties[$offset]);
	}


	// array functions define content and the actual substance of the class
	public function offsetGet($name)
	{
		return isset($this->content[$name]) ? $this->content[$name] : null;
	}

	public function offsetSet($name, $value)
	{
		return $this->content[$name] = $value;
	}

	public function offsetExists($name)
	{
		return isset($this->content[$name]);
	}

	public function offsetUnset($name)
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