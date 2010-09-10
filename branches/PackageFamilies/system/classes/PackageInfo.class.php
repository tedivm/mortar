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
	 * Current version of installed package
	 *
	 * @var Version
	 */
	protected $installedVersion;

	/**
	 * Version of package on filesystem
	 *
	 * @var Version
	 */
	protected $packageVersion;

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


	protected $cacheDisabled = false;


	static public function loadByPath($path)
	{
		if(!is_dir($path))
			throw new PackageInfoError('Unable to locate package at' . $path);

		$packageObject = new PackageInfo();
		$packageObject->buildFromPath($path);
		return $packageObject;
	}

	static public function loadByName($family, $name)
	{
		if($family === 'orphan')
			$family = null;

		$packageObject = new PackageInfo();
		$packageObject->buildByName($family, $name);

		AutoLoader::addModule($packageObject);

		return $packageObject;
	}

	static public function loadById($id)
	{
		$cache = CacheControl::getCache('packages', 'moduleLookup', $id);
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
				$package['name'] = $row['package'];
				$package['family'] = $row['family'];
			}else{
				$package = false;
			}
			$cache->storeData($package);
		}

		if(!$package)
			return false;

		return self::loadByName($package['family'], $package['name']);
	}


	protected function __construct($options = array())
	{
		if(isset($options['cache']) && $options['cache'] === false)
			$this->cacheDisabled = true;
	}

	protected function buildFromPath($path)
	{
		// we don't want 'new' packages interfering or loading information from the installed packages.
		$this->cacheDisabled = true;
		$this->path = $path;

		$this->name = $this->getMeta('name');

		if($metaFamily = $this->getMeta('family'))
		{
			$family = $metaFamily;
		}else{
			$family = 'orphan';
		}

		return $this->buildByName($family, $name);
	}

	protected function buildByName($family, $name)
	{
		if(!isset($name))
			return false;

		$this->name = $name;
		$this->family = isset($family) ? $family : 'orphan';

		if(!isset($this->path))
		{
			$config = Config::getInstance();
			$path = $config['path']['modules'];

			if($this->family !== 'orphan')
				$path .= $this->family . '/';

			$path .= $this->name . '/';

			if(!is_dir($path))
			{
				new PackageInfoInfo('Unable to find package ' . $this->family . ':' . $this->name . ' at ' . $path);
				return false;
			}

			$this->path = $path;
		}

		$cache = $this->getCache('info');
		$info = $cache->getData();
		if($cache->isStale())
		{
			try
			{
				if(!($db = db_connect('default_read_only')))
					return;

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
				}

			}catch(Exception $e){}

			if(!isset($info))
				$info['status'] = 'filesystem';


			$cache->storeData($info);
		}

		$this->status = $info['status'];

		if(isset($info['moduleId']))
		{
			$this->id = $info['moduleId'];
			$this->status = $info['status'];
			$this->installedVersion = new Version();
			$this->installedVersion->major = $info['majorVersion'];
			$this->installedVersion->minor = $info['minorVersion'];
			$this->installedVersion->micro = $info['microVersion'];
			$this->installedVersion->releaseType = $info['releaseType'];
			$this->installedVersion->releaseVersion = $info['releaseVersion'];
		}

		return true;
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
	 * @param bool $installed If passed false the filesystem version is returned instead of the installed version
	 * @return Version
	 */
	public function getVersion($installed = true)
	{
		if($installed)
		{
			if($this->getStatus() != 'installed')
				return false;

			return $this->installedVersion;
		}else{
			if(!isset($this->packageVersion))
				$this->loadMeta();

			return $this->packageVersion;
		}
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

	public function getFamily()
	{
		return $this->family;
	}

	public function getFullName()
	{
		if($this->family != 'orphan')
			$name = $this->family . $this->name;
		else
			$name = $this->name;

		return $name;
	}

	public function getClassName($classType, $name, $require = false)
	{
		$moduleFolders = array('abstract' => 'abstracts',
			'abstract' => 'abstracts',
			'actions' => 'actions',
			'action' => 'actions',
			'class'  => 'classes',
			'classes'  => 'classes',
			'control' => 'controls',
			'controls' => 'controls',
			'hook'  => 'hooks',
			'hooks'  => 'hooks',
			'interfaces'  => 'interfaces',
			'interface'  => 'interfaces',
			'library'  => 'library',
			'model' => 'models',
			'plugin' => 'plugins',
			'plugins' => 'plugins');

		$classType = strtolower($classType);
		if($classType == 'class')
		{
			$classDivider = '';
		}elseif(isset($moduleFolders[$classType])){
			$classDivider = ucwords($classType);
		}elseif($classDivider = array_search($classType, $moduleFolders)){
			$classDivider = ucwords($classDivider);
		}

		$className = $this->getFullName() . $classDivider . $name;
		return AutoLoader::internalClassExists($className) ? $className : false;
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

	/**
	 * This function returns the module's requirements from the php environment. Currently this is the minimum and
	 * maximum php version and extensions that are either required or recommended.
	 *
	 * @return array
	 */
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
		$cache = $this->getCache('meta');
		$meta = $cache->getData();
		if($cache->isStale())
		{
			$meta = self::getMetaInfo($this->getPath());
			$cache->storeData($meta);
		}

		if(isset($meta['php']))
		{
			$this->phpRequirements = $meta['php'];
			unset($meta['php']);
		}else{
			$this->phpRequirements = array();
		}

		$version = new Version();
		if($version->fromString(isset($meta['version']) ? $meta['version'] : '0 Alpha'))
			$this->packageVersion = $version;

		$this->meta = $meta;
	}

	static function getMetaInfo($path)
	{
		$cache = CacheControl::getCache('packages', $path, 'meta');
		$cache->setMemOnly(); // storing this would be ridiculous but memory saves us some includes
		$meta = $cache->getData();

		if($cache->isStale())
		{
			$metaPath = $path . '/package.ini';

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
		$cache = $this->getCache('actions');
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
						throw new PackageInfoInfo('Unable to load action class ' . $action['className'] .
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
							break;
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
		$cache = $this->getCache('plugins');
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
		$cache = $this->getCache('models');
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

	protected function getCache($key)
	{
		$cache = CacheControl::getCache('packages', $this->family, $this->name, $key);
		if($this->cacheDisabled)
			$cache->disable();

		return $cache;
	}
}

class PackageInfoError extends CoreError {}
class PackageInfoInfo extends CoreInfo {}
?>