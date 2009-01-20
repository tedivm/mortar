<?php

class PackageInfo
{

	public $name;
	public $path;
	public $actions;
	protected $meta;

	public function __construct($packageName)
	{

		if(is_null($packageName))
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
				$packageStmt->bind_param_and_execute('s', $packageName);

				if($moduleInfo = $packageStmt->fetch_array())
				{
						$info['majorVersion'] = $moduleInfo['moduleVersion'];
						$info['minorVersion'] = $moduleInfo['minorVersion'];
						$info['microVersion'] = $moduleInfo['microVersion'];
						$info['releaseType'] = $moduleInfo['releaseType'];
						$info['releaseVersion'] = $moduleInfo['releaseVersion'];
						$info['status'] = $moduleInfo['status'];
				}

			}catch(Exception $e){
				throw new BentoError('requested package ' . $this->name . ' does not exist');
				$info = false;
			}

			$cache->store_data($info);
		}

		$this->actions = $this->loadActions();
		$this->loadMeta();

		if(isset($info['status']))
		{
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

	public function getName()
	{
		return $this->name;
	}

	public function getActions()
	{
		return $this->actions;
	}

	public function packageHasAction($name)
	{
		return isset($this->actions[$name]);
	}

	public function getMeta($name)
	{
		return $this->meta[$name];
	}

	public function checkAuth($action)
	{
		$user = ActiveUser::get_instance();
		$allowed = false;

		if(is_array($this->installedModules))
			foreach($this->installedModules as $module)
		{
			$permission = new Permissions($module['locationId'], $user->getId());
			if($permission->isAllowed($action))
			{
				$allowed = true;
				break;
			}
		}

		return true;
		return $allowed;
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
		$cache = new Cache('packages', $packageName, 'actions');
		$info = $cache->getData();
		if(!$cache->cacheReturned)
		{
				// Load Actions
				$actionPaths = glob($this->path . 'actions/*.class.php');
				foreach($actionPaths as $filename)
				{
					try {
						$action = array();
						$tmpArray = explode('/', $filename);
						$tmpArray = array_pop($tmpArray);
						$tmpArray = explode('.', $tmpArray);
						$actionName = array_shift($tmpArray);
						//explode, pop. explode. shift

						$action['className'] = $this->name . 'Action' . $actionName;
						$action['path'] = $filename;

						if(!class_exists($action['className'], false))
						{

							if(!include($filename))
							{
								throw new BentoWarning('Unable to load action ' . $action['className'] .
														 ' file at: ' . $filename);
							}

							if(!class_exists($action['className'], false))
							{
								throw new BentoWarning('Action ' . $action['className'] . 'does not exist at' .
														 ' file at: ' . $filename);
							}
						}

						$actionReflection = new ReflectionClass($action['className']);

						if($actionReflection->isSubclassOf('Action'))
							$action['type'] = 'specificModule';

						if($actionReflection->isSubclassOf('PackageAction'))
							$action['type'] = 'genericPackage';

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

			}// end cache

			return $actions;
		}
	}

?>
