<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */


abstract class ModelBase implements Model
{
	/**
	 * This describes the 'type' of model the class represents, such as "Site", "User" or "Directory".
	 *
	 * @var string
	 */
	static public $type;

	/**
	 * This contains a non-static copy of the type variable. This is due to the fact that php < 5.3 does not handle
	 * static binding in a way we can work with and our hack around it is very resource intensive. This optimization
	 * will be removed when we stop supporting anything before php5.3
	 *
	 * @var string
	 */
	private $currentType;

	/**
	 * This is the table that the class will attempt to map to a model. If this is not set by the inheriting class then
	 * no database mapping will take place.
	 *
	 * @var string|null
	 */
	protected $table;

	/**
	 * This is the resource or model id representing the unique instance of the model.
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * This is the module that the resource type belongs to. This is set by the constructor.
	 *
	 * @var int
	 */
	protected $module;

	/**
	 * This is a list of properties that make up the model instance. Individual elements can be set using the standard
	 * object property interface (ie, $model->propertyName = $propertyValue);
	 *
	 * @var array
	 */
	protected $properties;

	/**
	 * This array contains all of the data that makes a model. If the $table property is set then this associate array
	 * will contain a all of the information from a single row, with the columns being the key. This array can be
	 * accessed by treating the model object like an array (ie, $model['name'] = 'value');
	 *
	 * @var array
	 */
	protected $content;

	/**
	 * This array contains the list of actions that have a fall back handler in the classes/modelSupport folder. This
	 * should be overloaded if eliminating or using different classes.
	 *
	 * @var array
	 */
	static public $fallbackModelActions = array('Read', 'Add', 'Edit', 'Delete', 'Index');

	/**
	 * This specificies what folder inside the modelSupport folder contains the model's fallback actions. Each array
	 * element is an additional directory level and name.
	 *
	 * @var array
	 */
	protected $backupActionDirectory = array('actions');

	/**
	 * This is the prefix added to fallback actions to get the name of the specific class the action uses. For instance,
	 * calling the 'Read' action would result in using the class ModelActionRead inside the
	 * system/modelSupport/actions/Read.class.php file.
	 *
	 * @var string
	 */
	protected $fallbackActionString = 'ModelAction';

	/**
	 * The constructor sets some basic properties and, if an ID is passed, initializes the model.
	 *
	 * @param null|int $id
	 */
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

	/**
	 * This function checks to see if the user is allowed to perform an action on this model. Unless overridden, this
	 * function performs by checking the root location to see whether someone can perform the action.
	 *
	 * @param string $action
	 * @param User|int|null $user If no argument is passed the active user is checked.
	 * @return bool
	 */
	public function checkAuth($action, $user = null)
	{
		if(!isset($user))
		{
			$user = ActiveUser::getUser();
			$user->getId();
			$userId = $user->getId();

		}elseif(is_numeric($user)){
			$userId = $user;
		}elseif($user instanceof Model && $user->getType() == 'User'){
			$userId = $user->getId();
		}else{
			throw new BentoError('Attempted to check permissions on a non user item.');
		}

		$location = new Location(1); // zee root
		$permissions = new Permissions($location, $user->getId());
		return $permissions->isAllowed($action, $this->getType());
	}

	/**
	 * This function converts the model into an array.
	 *
	 * @return array
	 */
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

	/**
	 * This function saves the model to the database. If the $table property is set then it will map the $content array
	 * directly to the columns of the same name in that table.
	 *
	 * @return bool
	 */
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
					{
						if(is_bool($this->content[$columnName]))
						{
							$value = ($this->content[$columnName]) ? 1 : 0;
						}else{
							$value = $this->content[$columnName];
						}
						$record->$columnName = $value;
					}
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

	/**
	 * If the table property is set this function will remove the row from that table with the same id (primary key)
	 * that the model contains. Custom implementations of the model class should make sure to override this class to
	 * clear out any additional data, and then (if desired) this function can be called.
	 *
	 * @return bool
	 */
	public function delete()
	{
		$id = $this->getId();
		if(!isset($id))
			throw new BentoError('Attempted to delete unsaved model.');

		if(isset($this->table))
		{
			$record = new ObjectRelationshipMapper($this->table);
			$record->primaryKey($id);

			if($record->select(1))
			{
				return $record->delete(1);
			}else{
				return true;
			}
		}
		return false;
	}

	/**
	 * When passed a simple action name (Read, Edit, Add, etc) this function returns the class name to perform that
	 * action. It first checks to see if the model's module (that sounds ridiculous) has an action that can
	 * be used, and if not it checks through the fallback action list. If nothing is found the
	 *
	 * @param string $actionName
	 * @return string|bool
	 */
	public function getAction($actionName)
	{
		$moduleActionName = $this->getType() . $actionName;
		$packageInfo = new PackageInfo($this->module);
		$actionInfo = $packageInfo->getActions($moduleActionName);

		return (!$actionInfo) ? $this->loadFallbackAction($actionName) : $actionInfo;
	}

	/**
	 * This function is used by the getAction function when no action is available in the module.
	 *
	 * @param string $actionName
	 * @return string|bool
	 */
	protected function loadFallbackAction($actionName)
	{
		if(in_array($actionName, staticHack(get_class($this), 'fallbackModelActions'))
			|| ($actionName == 'Execute' && method_exists($this, 'execute')) )
		{
			$pathArgs = $this->backupActionDirectory;
			$pathArgs[] = $actionName;

			if($path = call_user_func_array(array($this, 'getModelSupportFilePath'), $pathArgs))
				return array('className' => $this->fallbackActionString . $actionName, 'path' => $path);
		}
		return false;
	}

	/**
	 * This function returns an object that is used to convert the Model into a different format, such as Html or an
	 * array. These converts all have the "getOutput()" function, but otherwise can have very different implementations,
	 * so it is important to know what you are calling.
	 *
	 * @param string $format
	 * @return ModelConverter
	 */
	public function getModelAs($format)
	{
		$className = $this->getType() . 'To' . $format;
		if($path = $this->getModelFilePathFromPackage('Converters', $format))
		{
			if(!class_exists($className, false))
				include($path);
		}else{
			$className = 'ModelTo' . $format;
			if($path = self::getModelSupportFilePath('Converters', $format))
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

	/**
	 * This function takes in arguments mapping to a directory and file and attempts to load the file path from the
	 * module.
	 *
	 * @return string
	 */
	protected function getModelFilePathFromPackage()
	{
		$args = func_get_args();
		$package = new PackageInfo($this->getModule());
		$pathToPackage = $package->getPath();
		array_unshift($args, $pathToPackage);
		return self::getModelFilePath($args);
	}

	/**
	 * This function takes in arguments mapping to a directory and file and attempts to load the file path from the
	 * model support folder.
	 *
	 * @return string
	 */
	static protected function getModelSupportFilePath()
	{
		$args = func_get_args();
		$config = Config::getInstance();
		$path = $config['path']['mainclasses'] . 'modelSupport';
		array_unshift($args, $path);
		return self::getModelFilePath($args);
	}

	/**
	 * This converts an array into a filepath and checks to see if the file exists.
	 *
	 * @param array $args
	 * @return string
	 */
	static protected function getModelFilePath($args)
	{
		$path = '';
		foreach ($args as $pathPiece)
			$path .= '/' . $pathPiece;

		$path .= '.class.php';

		return (file_exists($path)) ? $path : false;

	}

	/**
	 * This function returns the entire content array. Unless
	 *
	 * @return array
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * This function returns the model's id.
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * This function returns the entire properties array.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * This function returns the model type (User, Page, etc).
	 *
	 * @return string
	 */
	public function getType()
	{
		if(!isset($this->currentType))
			$this->currentType = staticHack(get_class($this), 'type');

		return $this->currentType;
	}

	/**
	 * This function returns an identifier for the module the model class belongs to.
	 *
	 * @return int
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * This function is called by the constructor. If the $table property is set this function will load the row from
	 * that table with a primary key matching the passed $id and map each column to the content array.
	 *
	 * @cache models *type *id info
	 * @param int $id
	 * @return bool
	 */
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