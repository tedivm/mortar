<?php

class PackageInfo
{
	
	public $name;
	public $path;
	public $actions;
	public $installedModules;
	
	
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
				
				$packagePath = $config['path']['packages'] . $packageName;
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
		
		$this->actions = $info['actions'];
		$this->installedModules = $info['installedModules'];
		
		
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

	
	
	
	
	
}

class PackageInfoLink
{
	
}

?>