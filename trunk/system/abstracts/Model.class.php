<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */


class AbstractModel implements Model
{
	static public $type;

	protected $table;

	protected $id;

	protected $module;

	protected $properties;
	protected $content;

	static public $fallbackModelActions = array('Read', 'Add', 'Edit', 'Delete');

	public function __construct($id = null)
	{
		$handlerInfo = ModelRegistry::getHandler($this->getType());
		$this->module = $handlerInfo['module'];

		if(!is_null($id))
		{
			$this->load($id);
		}
	}

	public function checkAuth($action, $user = null)
	{

	}

	public function __toArray()
	{

	}

	public function save()
	{
		$db = DatabaseConnection::getConnection('default');
		$db->autocommit(false);

		try
		{
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
		if(!isset($this->id))
			throw new BentoError('Attempted to delete unsaved model.');

		if(isset($this->table))
		{
			$record = new ObjectRelationshipMapper($this->table);
			$record->primaryKey($this->id);

			if($record->select(1))
			{
				$record->delete(1);
			}else{

			}
		}
	}

	public function getAction($actionName)
	{
		$packageInfo = new PackageInfo($this->module);
		$moduleActionName = $this->getType() . $actionName;
		$actionInfo = $packageInfo->getActions($moduleActionName);

		return (!$actionInfo) ? $this->loadFallbackAction($actionName) :$actionInfo;
	}

	protected function loadFallbackAction($actionName)
	{
		if(in_array($actionName, staticHack(get_class($this), 'fallbackModelActions'))
			|| ($actionName == 'Execute' && method_exists($this, 'execute')) )
		{
			$actionInfo = array();
			$actionInfo['className'] = 'ModelAction' . $actionName;
			$config = Config::getInstance();
			$actionInfo['path'] = $config['path']['mainclasses'] . 'models/actions/' . $actionName . '.class.php';
			return $actionInfo;
		}else{
			return false;
		}
	}

	public function getContent()
	{
		return $this->content;
	}

	public function getId()
	{
		return $this->id;
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

	protected function load($id)
	{
		$cache = new Cache('models', $this->getType(), $id, 'info');
		$info = $cache->getData();

		if(!$cache->cacheReturned)
		{
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

}

?>