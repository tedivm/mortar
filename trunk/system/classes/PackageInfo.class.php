<?php

class PackageInfo
{
	public $id;
	public $name;
	public $path;
	public $actions;
	public $status;
	public $version;
	public $models;
	protected $meta;

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

	public function loadById($id)
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

			$cache->store_data($info);
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

	public function getPath()
	{
		return $this->path;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getStatus()
	{
		return ($this->status) ? $this->status : false;
	}


	public function getName()
	{
		return $this->name;
	}

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

	public function getModels()
	{
		return $this->models;
	}

	public function packageHasAction($name)
	{
		return isset($this->actions[$name]);
	}

	public function getMeta($name)
	{
		return $this->meta[$name];
	}

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
					if(!include($filename))
						throw new BentoWarning('Unable to load ' . $type . ' ' . $fileInfo['className'] .
												 ' file at: ' . $filename);

					if(!class_exists($fileInfo['className'], false))
						throw new BentoWarning($type . ' ' . $fileInfo['className'] . 'does not exist at' .
												 ' file at: ' . $filename);

				}

				$files[$fileClassName] = $fileInfo;
			}catch(Exception $e){

			}
		}
		return $files;
	}

}

?>
