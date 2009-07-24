<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Plugins
 */

/**
 * This class allows developers to easily load plugins into their own code
 *
 * @package System
 * @subpackage Plugins
 */
class Hook
{
	/**
	 * This is an array of loaded plugins (which are objects)
	 *
	 * @access protected
	 * @var array
	 */
	protected $plugins;

	/**
	 * This is an array of interfaces that plugins must implement
	 *
	 * @access protected
	 * @var array
	 */
	protected $interfaces = array();

	/**
	 * This method allows developers force plugins to implement an interface.
	 *
	 * @access public
	 * @param string $interface
	 */
	public function enforceInterface($interface)
	{
		if(!class_exists($interface, false))
			throw new BentoError('Attempting to add nonexistent interface ' . $interface . ' to plugin');

		$this->interfaces[] = $interface;
	}

	/**
	 * Returns all the currently active plugins.
	 *
	 * @access public
	 * @return array
	 */
	public function getPlugins()
	{
		return (isset($this->plugins)) ? $this->plugins : array();
	}

	/**
	 * When called this method will pull plugins from the database and load them for use.
	 *
	 * @access public
	 * @cache plugins *realm *category *name list
	 * @param string $realm This is a broad category used to describe a plugin and can be anything the author chooses
	 * @param string $category This is a subdivision of the $realm and can be anything the author chooses
	 * @param string $name This is the name of the specific hook you are calling and can be anything the author chooses
	 * @param string $onlyRecursive This is used to restrict plugin selection to those marked as recursive
	 * @return bool true if new plugins are loaded and added to the system, false otherwise
	 */
	public  function loadPlugins($realm, $category, $name, $onlyRecursive = false)
	{
		if(!isset($realm))
			throw new TypeMismatch(array('String or Location', $realm));

		if(!isset($category))
			throw new TypeMismatch(array('String', $category));

		if(!isset($name))
			throw new TypeMismatch(array('String', $name));

		$realm = ($realm instanceof Location) ? 'Resource' . $this->realm->getType() : $realm;

		$cache = new Cache('plugins', $realm, $category, $name, 'list');
		$pluginList = $cache->getData();

		if($cache->isStale())
		{
			$pluginList = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT module, plugin, isRecursive
								FROM plugins
								WHERE realm = ? AND category = ? AND name = ?');

			if($stmt->bindAndExecute('sss', $realm, $category, $name))
			{
				while($row = $stmt->fetch_array())
				{
					try{
						$className = importFromModule($row['plugin'], $row['module'], 'plugin');
						$classReflection = new ReflectionClass($className);
						foreach($this->interfaces as $interface)
						{
							if(!$classReflection->implementsInterface($interface))
								continue 2;
						}
						$pluginList[] = $row;
					}catch(Exception $e){}
				}
			}else{
				$pluginList = false;
			}
			$cache->storeData($pluginList);
		}

		$classList = array();
		foreach($pluginList as $plugin)
		{
			if($onlyRecursive && $plugin['isRecursive'] != 1)
				continue;

			$classList[] = importFromModule($plugin['plugin'], $plugin['module'], 'plugin');
		}

		$pluginObjects = array();
		foreach($classList as $class)
		{
			try {
				$newPlugin = new $class();
				$pluginObjects[] = $newPlugin;
			}catch(Exception $e){}
		}

		if(count($pluginObjects > 0))
		{
			$this->plugins = (is_array($this->plugins)) ? array_merge($this->plugins, $pluginObjects) : $pluginObjects;
			return true;
		}else{
			return false;
		}
	}

	/**
	 * This function loads plugins based off of a model instance and a plugin name. It loads all of the plugins for that
	 * particular model type, the model types that it inherits from (if the plugins are marked to be inherited), and any
	 * plugin belonging to the model type "All".
	 *
	 * @param Model $model
	 * @param String $name
	 * @return bool
	 */
	public function loadModelPlugins(Model $model, $name)
	{
		$modelClass = get_class($model);
		$restrict = false;

		$hasPlugins = $this->loadPlugins('models', 'all', $name, $restrict);

		do
		{
			$parentType = staticHack($modelClass, 'type');
			if(isset($parentType))
			{
				$hasPlugins = ($this->loadPlugins('model', $parentType, $name, $restrict) || $hasPlugins);
				$restrict = true;
			}else{
				break;
			}
		}while($modelClass = get_parent_class($modelClass));

		return $hasPlugins;
	}


	/**
	 * This magic method allows developers to run functions across all of the plugins at once by calling them on the
	 * hook object.
	 *
	 * @access public
	 * @param string $name this is the name of the function
	 * @param array $arguments this is the array of arguments passed
	 * @return array Each element will be a plugin result
	 */
	public function __call($name, $arguments)
	{
		$responses = array();
		$plugins = $this->getPlugins();
		foreach($plugins as $plugin)
		{
			try{
				if(method_exists($plugin, $name))
				{
					// we seperate the function call from the assignment to the response array in order to deal with
					// any errors.
					$response = call_user_func_array(array($plugin, $name), $arguments);
					$responses[] = $response;
				}
			}catch(Exception $e){

			}
		}
		return $responses;
	}

	/**
	 * This static function is used to register new plugins into the system. The realm, category and name parameters
	 * should match up to a Hook->loadPlugins call somewhere in the system.
	 *
	 * @param string $realm
	 * @param string $category
	 * @param string $name
	 * @param int $module
	 * @param string $plugin
	 * @param bool $isRecursive
	 */
	static public function registerPlugin($realm, $category, $name, $module, $plugin, $isRecursive = false)
	{
		$isRecursive = $isRecursive ? 1 : 0;
		$db = DatabaseConnection::getConnection('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT
							INTO plugins (realm, category, name, module, plugin, isRecursive)
							VALUES (?, ?, ?, ?, ?, ?)');
		$stmt->bindAndExecute('sssisi', $realm, $category, $name, $module, $plugin, $isRecursive);
		Cache::clear('plugins', $realm, $category, $name);
	}

	static public function registerModelPlugin($type, $name, $module, $plugin, $isRecursive = false)
	{
		return self::registerPlugin('model', $type, $name, $module, $plugin, $isRecursive);
	}

}


?>