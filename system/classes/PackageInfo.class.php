<?php

class PackageInfo
{
	
	public $name;
	public $path;
	public $actions;
	public $installedModules;
	protected $meta;
	
	public function __construct($packageName)
	{

		if(is_null($packageName))
			throw new BentoError('Class PackageInfo constructor expects one arguement');
		
		$config = Config::getInstance();
		$this->name = $packageName;
		
		$cache = new Cache('packages', $packageName, 'info');
		$info = $cache->get_data();
		if(!$cache->cacheReturned)
		{
			try {
				
				$db = db_connect('default_read_only');
				
				$packagePath = $config['path']['modules'] . $packageName;
				if(file_exists($packagePath))
				{
					$path = $packagePath;
				}else{
					throw new BentoWarning('Unable to locate directory: ' . $packagePath . ' when loading: ' . $packageName);
				}
				
				$info['path'] = $packagePath;
				// Load Actions

				$actionPaths = glob($path . '/actions/*.class.php');
				foreach($actionPaths as $filename)
				{
					try {
						$action = array();
						$actionName = array_shift(explode('.', array_pop(explode('/', $filename))));
						$action['className'] = $this->name . 'Action' . $actionName;
						$action['path'] = $filename;
						
						if(!class_exists($action['className'], false))
						{
							include($filename);
							if(!class_exists($action['className'], false))
							{
								throw new BentoWarning('Unable to load action ' . $action['className'] . ' file at: ' . $filename);
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
							//	$engine['type'] = ($method->isStatic()) ? 'generic' : 'moduleSpecific';
								
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
						$info['actions'][$actionName] = $action;

					}catch(Exception $e){
						
					}

									
				}
				
				
				
				// Load Module IDs
				
				$packageStmt = $db->stmt_init();
				
				$packageStmt->prepare('SELECT mod_id, location_id FROM modules WHERE mod_package = ?');
				$packageStmt->bind_param_and_execute('s', $packageName);
				
				
				while($modules = $packageStmt->fetch_array())
				{
					$info['installedModules'][] = array('modId' => $modules['mod_id'], 'locationId' => $modules['location_id']);
				}
						
				
			}catch(Exception $e){
				$info = false;
			}
			
			$cache->store_data($info);

		}

		$this->path = $info['path'] . '/';
		$this->actions = $info['actions'];
		$this->installedModules = $info['installedModules'];
		$this->loadMeta();
		
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
	
	public function getMeta($name)
	{
		return $this->meta[$name];
	}
	
	public function getModules($requiredPermission = '')
	{
		$requiredPermission = 'Add';
		if(strlen($requiredPermission) > 0)
		{
			$user = ActiveUser::getInstance();
			$outputModules = array();
			foreach($this->installedModules as $module)
			{
			//	var_dump($module);
				$moduleInfo = new ModuleInfo($module['modId']);
				
				if($moduleInfo->checkAuth($requiredPermission))
				{
					$outputModules[] = $module;
				}
			}
		}else{
			$outputModules = $this->installedModules;
		}
		
		return $outputModules;
	}
	
	public function checkAuth($action)
	{
		$user = ActiveUser::get_instance();
		$allowed = false;
		
		foreach($this->installedModules as $module)
		{
			$permission = new Permissions($module['locationId'], $user->id);
			if($permission->is_allowed($action))
			{
				$allowed = true;
				break;
			}
		}

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
	
	
	
}

class PackageInfoLink
{
	
}

?>