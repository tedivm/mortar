<?php
$config = Config::getInstance();

if(!class_exists('Version', false))
{
	include($config['path']['mainclasses'] . 'version.class.php');
}

class InstallModule
{
	
	protected $package;
	protected $settings = array();
	protected $parentLocation;
	protected $name;
	
	protected $pathToPackage;
		
	public $id;
	public $location;
	
	public function __construct($package, $name, $parentLocation, $settings = '')
	{
		$this->package = $package;
		$this->name = $name;
		$this->parentLocation = ($parentLocation instanceof Location) ? $parentLocation->getId() : $parentLocation;
		
		$location = new Location($this->parentLocation);

		$child = $location->getChildByName($name);
		if($location->getChildByName($name))
			throw new BentoError('You can not create two locations with the same name and parent.');
		
		$config = Config::getInstance();
		$this->pathToPackage = $config['path']['modules'] . $this->package . '/';
		if(is_array($settings) && count($settings) > 0)
		{
			$this->settings = $settings;
		}else{
			
		}
			
			
	}
	
	

	
	public function installModule()
	{
		
		try{
				
			$config = Config::getInstance();
			$pathToPackage = $config['path']['modules'] . $this->package . '/';
			$version = new Version();
			
			if(file_exists($pathToPackage . 'meta.php'))
			{
				include($pathToPackage . 'meta.php');
				$versionString = $version;
				
				$version = new Version();
				$version->fromString($versionString);
			}
			
			$packageInstalled = new ObjectRelationshipMapper('package_installed');
			$packageInstalled->name = $this->package;
			
			if($packageInstalled->select())
			{
				
				$packageVersion = new Version();
				$packageVersion->major = $packageInstalled->majorVersion;
				$packageVersion->minor = $packageInstalled->mminorVersion;
				$packageVersion->micro = $packageInstalled->microVersion;
				$packageVersion->releaseType = $packageInstalled->prereleaseType;
				$packageVersion->releaseVersion = $packageInstalled->prereleaseVersion;
				
				if($version->compare($packageVersion) > 0)
				{
					// update
				}
				
				
			}else{
				
				if(is_dir($pathToPackage))
				{
					$packageInstalled->status = 'filesystem';
				}else{
					// well this is a problem
					throw new BentoError('Package does not exist');
				}
				
				
				$packageInstalled->majorVersion = $version->major;
				$packageInstalled->minorVersion = $version->minor;
				$packageInstalled->microVersion = $version->micro;						
				$packageInstalled->releaseType = $version->releaseType;			
				$packageInstalled->releaseVersion = $version->releaseVersion;
				$packageInstalled->status = 'filesystem';
				
				if(!$packageInstalled->save())
				{
					throw new BentoError('Unable to update package database with version information.');
				}
				
			}
			
			
			// PreInstallation
			switch ($packageInstalled->status) {
	
				case 'filesystem':
					// Set up the Database
					$sqlPath = $pathToPackage . 'sql/install.sql.php';
					if(file_exists($sqlPath))
					{
						$sql = file_get_contents($sqlPath);
						$db = db_connect('default');
						
						if ($db->multi_query($sql)) 
						{
							do {
								if ($result = $db->store_result()) {
									$result->free();
								}
								if ($db->more_results()) {
								}
							} while ($db->next_result());
						}
					}
					$packageInstalled->status = 'database';
					$packageInstalled->save();										
					break;
			
				case 'database':
				case 'installed':
					
					break;
			}
			
			
			
			// Create Location
			$location = new Location();
			$location->parent = $this->parentLocation;
			$location->resource = 'Module';
			$location->name = $this->name;
			$location->save();
			$this->location = $location;
			
			// Insert Module Data to Database
			
			
			$module = new ObjectRelationshipMapper('modules');
			$module->location_id = $this->location->getId();
			$module->mod_name = $this->name;
			$module->mod_package = $this->package;
			
			
			$module->save();
			$this->id = $module->mod_id;
			
			
			// Load settings
			
			foreach($this->settings as $name => $value)
			{
				$setting = new ObjectRelationshipMapper('mod_config');
				$setting->mod_id = $this->id;
				$setting->name = $name;
				$setting->value = $value;
				$setting->save();
			}
			
			
			
			// Populate Plugin Lookup Table
	
			$pluginPattern = $pathToPackage . 'hooks/*.php';
			$classPlugins = glob($pluginPattern);		
				
			foreach($classPlugins as $fileName)
			{
				try{

				$tmpArray = explode('.', array_pop(explode('/', $fileName)));		
				$pluginName = $tmpArray[0];
				$tmpArray = explode('-', $tmpArray[1]);
				$hookType = $tmpArray[0];
				$hookName = $tmpArray[1];
				$className = $this->package . $pluginName . $hookType . $hookName;

				
				if($hookType == 'Internal')
					throw new BentoNotice('Plugin Type not valid or internal, skipping: ' . $className);
					
				if(!class_exists($className, false))
				{
					include($fileName);
				}
				
				if(is_callable(array($className, 'scope')))
					$scope =  staticFunctionHack($className, 'scope', $this->id);

				if(!isset($scope))
					$scope = staticHack($className, 'scope');

				if($scope instanceof Location)
					$scope = $scope->id;
					
				
				switch ($hookType) {
					case 'Module':
						$pluginRow = new ObjectRelationshipMapper('plugin_lookup_module');
						$pluginRow->module_package = $scope;
						break;
				
					case 'Rngine':
						$pluginRow = new ObjectRelationshipMapper('plugin_lookup_engine');
						$pluginRow->plugin_engine = $scope;
						break;
						
					case 'Location':
						$pluginRow = new ObjectRelationshipMapper('plugin_lookup_location');
						$pluginRow->location_id = $scope;
						break;						
						
					case 'Internal':	
					default:
						throw new BentoNotice('Plugin Type not valid or internal, skipping: ' . $className);
						break;
				}

				$pluginRow->mod_id = $this->id;
				$pluginRow->name = $pluginName;
				$pluginRow->hook = $hookName;
				if(!$pluginRow->save())
					throw new BentoError('Unable to install plugin to the database: ' . $className);
					
					
					
					
				}catch (Exception $e){
					
				}
			}
			
			
			// Check Action Permissions
			$actionPattern = $pathToPackage . 'actions/*.class.php';
			$classActions = glob($actionPattern);		
			$allPermissions = array();	
			foreach($classActions as $fileName)
			{
				$tmpArray = explode('/', $fileName);
				$tmpArray = explode('.', array_pop($tmpArray));
				//action
				$actionInfo['action'] = $tmpArray[0];
				
				$className = $this->package . 'Action' . $actionInfo['action'];
				

					
				if(!class_exists($className, false) && file_exists($fileName))
				{
					include($fileName);
				}
	
				$permission = staticHack($className, 'requiredPermission'); // Dirty hack until php 5.3	
				
				if($permission && !in_array($permission, $allPermissions))
					$allPermissions[] = $permission;
				
			}
			
			$adminProfile = new ObjectRelationshipMapper('permission_profiles');
			$adminProfile->perprofile_name = 'Full Permissions';
			$adminProfile->save();
			
			foreach($allPermissions as $permission)
			{
				
				$actionRow = new ObjectRelationshipMapper('actions');
				$actionRow->action_name = $permission;
				
				if($actionRow->select())
				{
					continue;
				}else{
			
					// We want to make sure the admin classes can do anything when and where they are applied
					
					$actionRow->save();
					
					
					//$permissionProfileLink = new ObjectRelationshipMapper('permissionprofile_has_actions');
					
					
					
					//$permissionProfileLink->perprofile_id = $adminProfile->perprofile_id;
					//$permissionProfileLink->action_id = $actionRow->action_id;
					//$permissionProfileLink->permission_status = '1';
					
						
				}
							
			}
			
			// Run Post Install
			
			$customPath = $pathToPackage . 'hooks/' . 'Installation.Internal.php';
			$className = $this->package . 'Install';
			if(class_exists($className, false) || (file_exists($customPath) && include($customPath) && class_exists($classActions, false)))
			{
				$customCode = new $className($this->id);
				$customCode->run();
			}
			
			$packageInstalled->status = 'installed';
			$packageInstalled->save();
			// Clear Cache
			Cache::clear();
			
			

			
			
		}catch(Exception $e){
			
			return false;
			
		}
		
		return true;
	}
}





?>