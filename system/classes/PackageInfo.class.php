<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Module
 */

/**
 * This class contains information about a package (the files that make up a module)
 *
 * @package System
 * @subpackage Module
 */
class PackageInfo
{
	/**
	 * If installed, this is the current module id
	 *
	 * @var int
	 */
	public $id;

	/**
	 * This is the name of the package
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Path to the package
	 *
	 * @var string
	 */
	public $path;

	/**
	 * An array of actions the module contains
	 *
	 * @var array
	 */
	public $actions;

	/**
	 * The current status of a package
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Current version of a package
	 *
	 * @var Version
	 */
	public $version;

	/**
	 * A list of models the package contains
	 *
	 * @var unknown_type
	 */
	public $models;

	/**
	 * Meta data about the package, as found in its information file
	 *
	 * @access protected
	 * @var array
	 */
	protected $meta;

	/**
	 * Constructor takes the name of id of the package
	 *
	 * @cache packages moduleLookup *id
	 * @param id|string $package
	 */
	public function __construct($package)
	{
		if(is_numeric($package))
		{
			$this->loadById($package);
		}elseif(is_string($package)){
			$this->loadByName($package);
		}else{
			throw new TypeMismatch(array('String or Integer', $package));
		}
	}

	/**
	 * Loads the module name by id then returns the results of loadByName
	 *
	 * @access protected
	 * @cache packages moduleLookup *id
	 * @see loadByName
	 * @param int $id
	 */
	protected function loadById($id)
	{
		$cache = new Cache('packages', 'moduleLookup', $id);
		$package = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$packageStmt = $db->stmt_init();
			$packageStmt->prepare('SELECT * FROM modules WHERE mod_id = ?');
			$packageStmt->bindAndExecute('i', $id);

			if($packageStmt->num_rows == 1)
			{
				$row = $packageStmt->fetch_array();
				$package = $row['package'];
			}else{
				$package = false;
			}
			$cache->storeData($package);
		}

		$this->loadByName($package);
	}

	/**
	 * Loads the package by name
	 *
	 * @access protected
	 * @cache packages *packageName info
	 * @param string $packageName
	 */
	public function loadByName($packageName)
	{
		if(is_null($packageName) || strlen($packageName) < 1)
			throw new BentoError('Class PackageInfo constructor expects one arguement');

		$config = Config::getInstance();
		$this->name = $packageName;

		$path = $config['path']['modules'] . $packageName . '/';
		if(is_dir($path))
		{
			$this->path = $path;
		}else{
			throw new BentoError('Unable to locate package ' . $this->name);
		}


		$cache = new Cache('packages', $packageName, 'info');
		$info = $cache->getData();
		if(!$cache->cacheReturned)
		{
			try {

				AutoLoader::import($this->name);
				$db = db_connect('default_read_only');


				$packageStmt = $db->stmt_init();
				$packageStmt->prepare('SELECT * FROM modules WHERE package = ?');
				$packageStmt->bindAndExecute('s', $packageName);

				if($moduleInfo = $packageStmt->fetch_array())
				{
					$info['moduleId'] = $moduleInfo['mod_id'];
					$info['majorVersion'] = $moduleInfo['majorVersion'];
					$info['minorVersion'] = $moduleInfo['minorVersion'];
					$info['microVersion'] = $moduleInfo['microVersion'];
					$info['releaseType'] = $moduleInfo['releaseType'];
					$info['releaseVersion'] = $moduleInfo['releaseVersion'];
					$info['status'] = $moduleInfo['status'];
				}else{
					$info['status'] - 'filesystem';
				}

			}catch(Exception $e){
				throw new BentoError('requested package ' . $this->name . ' does not exist');
				$info = false;
			}

			$cache->storeData($info);
		}

		$this->actions = $this->loadActions();
		$this->models = $this->loadModels();
		$this->loadMeta();

		$this->status = $info['status'];

		if(isset($info['moduleId']))
		{
			$this->id = $info['moduleId'];
			$this->status = $info['status'];
			$this->version = new Version();
			$this->version->major = $info['majorVersion'];
			$this->version->minor = $info['minorVersion'];
			$this->version->micro = $info['microVersion'];
			$this->version->releaseType = $info['releaseType'];
			$this->version->releaseVersion = $info['releaseVersion'];
		}

	}

	/**
	 * Returns the path to the package
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Returns the id of the installed module, or false if it is not installed
	 *
	 * @return int|bool
	 */
	public function getId()
	{
		return isset($this->id) ? $this->id : false;
	}

	/**
	 * Returns the current status of the package, or false if its not found
	 *
	 * @return string|bool
	 */
	public function getStatus()
	{
		return ($this->status) ? $this->status : false;
	}

	/**
	 * Returns the name of the package
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Returns a specific action or the entire action array
	 *
	 * @param string|null $actionName
	 * @return array
	 */
	public function getActions($actionName = null)
	{
		if($actionName)
		{
			if(!$this->packageHasAction($actionName))
				return false;

			return $this->actions[$actionName];
		}
		return $this->actions;
	}

	/**
	 * Returns all of the models the package contains
	 *
	 * @return array
	 */
	public function getModels()
	{
		return $this->models;
	}

	/**
	 * Checks to see if a package contains an action
	 *
	 * @param string $name
	 * @return bool
	 */
	public function packageHasAction($name)
	{
		return isset($this->actions[$name]);
	}

	/**
	 * Returns the meta value about the package
	 *
	 * @param string $name
	 * @return string
	 */
	public function getMeta($name)
	{
		return $this->meta[$name];
	}

	/**
	 * Loads the meta data from the package
	 *
	 * @access protected
	 */
	protected function loadMeta()
	{
		$metaPath = $this->path . 'meta.php';

		if(is_readable($metaPath))
		{
			include $metaPath;

			$meta['name'] = $packageName;
			$meta['version'] = $version;
			$meta['description'] = $description;
			$this->meta = $meta;

		}
	}

	/**
	 * Loads the actions from the package
	 *
	 * @access protected
	 * @cache packages *packageName actions
	 * @return array
	 */
	protected function loadActions()
	{
		$cache = new Cache('packages', $this->name, 'actions');
		$actions = $cache->getData();
		if(!$cache->cacheReturned)
		{
			$actions = array();
			// Load Actions
			$actionNames = $this->loadClasses('action');
			foreach($actionNames as $actionName => $action)
			{
				try {
					$actionReflection = new ReflectionClass($action['className']);
					$methods = $actionReflection->getMethods();
					$engines = array();
					foreach($methods as $method)
					{
						$engine = array();
						if(strpos($method->name, 'view') === 0)
						{
							$engineName = substr($method->name, '4');
							$settings = $engineName . 'Settings';
							if($actionReflection->hasProperty($settings))
							{
								$properties = get_class_vars($action['className']);
								$engine['settings'] = $properties[$settings];
							}
							$engines[$engineName] = $engine;
						}
					}
					$action['engineSupport'] = $engines;
					$action['permissions'] = staticHack($action['className'], 'requiredPermission');
					$actions[$actionName] = $action;
				}catch(Exception $e){

				}
			}

			$cache->storeData($actions);
		}// end cache

		return $actions;
	}

	/**
	 * Loads the plugins the package contains
	 *
	 * @access protected
	 * @cache packages *packageName plugins
	 * @return array
	 */
	protected function loadPlugins()
	{
		$cache = new Cache('packages', $this->packageName, 'plugins');
		$plugins = $cache->getData();

		if($cache->cacheReturned)
		{
			$plugins = array();
			$pluginFiles = $this->loadClasses('plugins');
			foreach($pluginFiles as $pluginFile)
			{

			}
		}


		return $plugins;
	}

	/**
	 * Loads the models from the package
	 *
	 * @access protected
	 * @cache packages *packageName models
	 * @return array
	 */
	protected function loadModels()
	{
		$cache = new Cache('packages', $this->name, 'models');
		$models = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$models = array();
			$modelFiles = $this->loadClasses('model');
			foreach($modelFiles as $modelFile)
			{
				$models[$modelFile['name']]['type'] = staticHack($modelFile['className'], 'type');
				$models[$modelFile['name']]['className'] = $modelFile['className'];
				$models[$modelFile['name']]['name'] = $modelFile['name'];
				$models[$modelFile['name']]['path'] = $modelFile['path'];
			}
			$cache->storeData($models);
		}

		return $models;
	}

	/**
	 * This is a utility method called by the load* functions to iterate through a directory for classes
	 *
	 * @param string $type Type of class to look for (action, plugin, model, etc)
	 * @return array
	 */
	protected function loadClasses($type)
	{
		$filePaths =  glob($this->path . $type  . 's/*.class.php');
		$typeDelimiter = ucfirst(strtolower($type));
		$files = array();
		foreach($filePaths as $filename)
		{
			try {
				$fileInfo = array();
				$tmpArray = explode('/', $filename);
				$tmpArray = array_pop($tmpArray);
				$tmpArray = explode('.', $tmpArray);
				$fileClassName = array_shift($tmpArray);
				//explode, pop. explode. shift

				$fileInfo['name'] = $fileClassName;
				$fileInfo['className'] = $this->name . $typeDelimiter . $fileClassName;
				$fileInfo['path'] = $filename;

				if(!class_exists($fileInfo['className'], false))
				{

					if(!include($fileInfo['path']))
					{
						$e = new Exception('Unable to load ' . $type . ' ' . $fileInfo['className']
										. ' from ' . $fileInfo['path']);
						RequestLog::logError($e, '3');
						continue;
					}

					if(!class_exists($fileInfo['className'], false))
					{
						$e = new Exception('Unable to load ' . $type . ' ' . $fileInfo['className']
										. ' from ' . $fileInfo['path']);
						RequestLog::logError($e, '3');
						continue;
					}
				}
				$files[$fileClassName] = $fileInfo;
			}catch(Exception $e){

			}
		}
		return $files;
	}

}

?>
