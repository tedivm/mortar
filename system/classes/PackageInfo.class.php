<?php
/**
 * Mortar
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

	protected $phpRequirements;

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
		AutoLoader::addModule($package);
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

		if($cache->isStale())
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
			throw new PackageInfoError('Class PackageInfo constructor expects one arguement');

		$config = Config::getInstance();
		$this->name = $packageName;

		$path = $config['path']['modules'] . $packageName . '/';
		if(is_dir($path))
		{
			$this->path = $path;
		}else{
			throw new PackageInfoError('Unable to locate package ' . $this->name);
		}

		$cache = new Cache('packages', $packageName, 'info');
		$info = $cache->getData();
		if($cache->isStale())
		{
			try {

				//AutoLoader::import($this->name);
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
				throw new PackageInfoError('requested package ' . $this->name . ' does not exist');
				$info = false;
			}

			$cache->storeData($info);
		}

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
	 * Returns the version of the installed module.
	 *
	 * @return Version
	 */
	public function getVersion()
	{
		if($this->getStatus() != 'installed')
			return false;

		return $this->version;
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
		if(!isset($this->actions))
			$this->actions = $this->loadActions();

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
		if(!isset($this->models))
			$this->models = $this->loadModels();

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
		$actions = $this->getActions();
		return isset($actions[$name]);
	}

	/**
	 * Returns the meta value about the package
	 *
	 * @param string $name
	 * @return string
	 */
	public function getMeta($name = null)
	{
		if(!isset($this->meta))
			$this->loadMeta();

		if(is_null($name))
			return $this->meta;

		if(isset($this->meta[$name]))
			return $this->meta[$name];

		return false;
	}


	public function getPhpRequirements()
	{
		if(!isset($this->phpRequirements))
			$this->loadMeta();

		return $this->phpRequirements;
	}


	/**
	 * Loads the meta data from the package
	 *
	 * @access protected
	 */
	protected function loadMeta()
	{
		$meta = self::getMetaInfo($this->getName());

		if(isset($meta['php']))
		{
			$this->phpRequirements = $meta['php'];
			unset($meta['php']);
		}else{
			$this->phpRequirements = array();
		}

		$this->meta = self::getMetaInfo($this->getName());
	}

	static function getMetaInfo($package)
	{
		$cache = new Cache('packages', $package, 'meta');
		$cache->setMemOnly(); // storing this would be ridiculous but memory saves us some includes
		$meta = $cache->getData();

		if($cache->isStale())
		{
			$config = Config::getInstance();
			//$metaPath = $config['path']['modules'] . $package . '/meta.php';

			$metaPath = $config['path']['modules'] . $package . '/package.ini';

			$meta = array();

			if(is_readable($metaPath))
			{

				$packageIni = new IniFile($metaPath);
				$meta['name'] = $packageIni->get('General', 'name');
				$meta['version'] = $packageIni->get('General', 'version');
				$meta['description'] = $packageIni->get('General', 'description');

				if($disableInstall = $packageIni->get('General', 'disableInstall'))
					$meta['disableInstall'] = (bool) $disableInstall;

				if($packageIni->exists('PHP'))
				{
					if($packageIni->exists('PHP', 'version.min'))
						$meta['php']['version']['min'] = $packageIni->get('PHP', 'version.min');

					if($packageIni->exists('PHP', 'version.max'))
						$meta['php']['version']['max'] = $packageIni->get('PHP', 'version.max');

					if($packageIni->exists('PHP', 'required'))
					{
						$requiredExtensions = $packageIni->get('PHP', 'required');
						$meta['php']['extensions']['required'] = explode(',', $requiredExtensions);
					}

					if($packageIni->exists('PHP', 'recommended'))
					{
						$requiredExtensions = $packageIni->get('PHP', 'recommended');
						$meta['php']['extensions']['recommended'] = explode(',', $requiredExtensions);
					}
				}
			}
			$cache->storeData($meta);
		}
		return $meta;
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
		if($cache->isStale())
		{
			$actions = array();
			// Load Actions
			$actionNames = $this->loadClasses('action');
			foreach($actionNames as $actionName => $action)
			{
				try {

					if(!class_exists($action['className']))
						throw new CoreWarning('Unable to load action class ' . $action['className'] .
								' from module ' . $this->name);

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
					$modelNames = ModelRegistry::getModelList();
					natsort($modelNames);                       // This is a hack to ensure that the longest
					$modelNames = array_reverse($modelNames);   // possible classnames are tried first
					foreach($modelNames as $name) {
						if (strpos($action['name'], $name) === 0) {
							$action['outerName'] = substr($action['name'], strlen($name));
							$action['type'] = $name;
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

		if($cache->isStale())
		{
			$plugins = array();
			$pluginFiles = $this->loadClasses('plugins');
			foreach($pluginFiles as $pluginFile)
			{
				$plugins[$pluginFile['name']] = $pluginFile['classname'];
				$plugins[$pluginFile['name']] = $pluginFile['path'];

			}
			$cache->storeData($plugins);
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

		if($cache->isStale())
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
				$files[$fileClassName] = $fileInfo;
			}catch(Exception $e){

			}
		}
		return $files;
	}

	static function checkModuleStatus($moduleName)
	{
		$config = Config::getInstance();
		$pathToModule = $config['path']['modules'] . $moduleName;

		if(!file_exists($pathToModule))
			return false;

		$packageInfo = new PackageInfo($moduleName);

		if(!$status = $packageInfo->getStatus())
			return 'filesystem';

		return $status;
	}

}

class PackageInfoError extends CoreError {}
?>
