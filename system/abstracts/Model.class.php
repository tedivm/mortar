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
	private $currentType;

	protected $table;

	protected $id;

	protected $module;

	protected $properties;
	protected $content;

	static public $fallbackModelActions = array('Read', 'Add', 'Edit', 'Delete', 'Index');
	protected $backupActionDirectory = array('actions');

	public function __construct($id = null)
	{
		$handlerInfo = ModelRegistry::getHandler($this->getType());
		$this->module = $handlerInfo['module'];

		if(is_numeric($id))
			$id = (int) $id;

		if(!is_null($id))
		{
			if(!$this->load($id))
				throw new BentoError('No model of type ' . $this->getType() . ' with id ' . $id);
		}
	}

	public function checkAuth($action, $user = null)
	{

	}

	public function __toArray()
	{
		$array = array();
		$array['id'] = $this->getId();
		$array['type'] = $this->getType();

		if(isset($this->properties))
			$array['properties'] = $this->properties;

		if(isset($this->content))
			$array = array_merge($array, $this->content);

		return $array;
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

			Cache::clear('models', $this->getType(), $this->id);

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

		return (!$actionInfo) ? $this->loadFallbackAction($actionName) : $actionInfo;
	}

	protected function loadFallbackAction($actionName)
	{
		if(in_array($actionName, staticHack(get_class($this), 'fallbackModelActions'))
			|| ($actionName == 'Execute' && method_exists($this, 'execute')) )
		{
			$pathArgs = $this->backupActionDirectory;
			$pathArgs[] = $actionName;

			if($path = call_user_func_array(array($this, 'getModelSupportFilePath'), $pathArgs))
				return array('className' => 'ModelActionLocationBased' . $actionName, 'path' => $path);
		}
		return false;
	}

	public function getModelAs($format)
	{
		$className = $this->getType() . 'To' . $format;
		if($path = $this->getModelFilePathFromPackage('Converters', $format))
		{
			if(!class_exists($className, false))
				include($path);
		}else{
			$className = 'ModelTo' . $format;
			if($path = $this->getModelSupportFilePath('Converters', $format))
			{
				if(!class_exists($className, false))
					include($path);
			}
		}

		if(!class_exists($className, false))
			return false;

		$modelConverter = new $className($this);
		return $modelConverter;
	}

	protected function getModelFilePathFromPackage()
	{
		$args = func_get_args();
		$package = new PackageInfo($this->getModule());
		$pathToPackage = $package->getPath();
		array_unshift($args, $pathToPackage);
		return $this->getModelFilePath($args);
	}

	protected function getModelSupportFilePath()
	{
		$args = func_get_args();
		$config = Config::getInstance();
		$path = $config['path']['mainclasses'] . 'modelSupport';
		array_unshift($args, $path);
		return $this->getModelFilePath($args);
	}

	protected function getModelFilePath($args)
	{
		$path = '';
		foreach ($args as $pathPiece)
			$path .= '/' . $pathPiece;

		$path .= '.class.php';

		return (file_exists($path)) ? $path : false;

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
		if(!isset($this->currentType))
			$this->currentType = staticHack(get_class($this), 'type');

		return $this->currentType;
	}

	public function getModule()
	{
		return $this->module;
	}

	protected function load($id)
	{
		$cache = new Cache('models', $this->getType(), $id, 'info');
		$info = $cache->getData();

		if($cache->isStale())
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
				}else{
					$info = false;
				}
			}else{
				$info['id'] = $id;
			}
			$cache->storeData($info);
		}

		if($info === false)
			return false;

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