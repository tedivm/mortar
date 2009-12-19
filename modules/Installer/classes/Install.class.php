<?php

class InstallerInstaller // thats the most pathetic name ever
{
	/**
	 * Installation profile.
	 *
	 * @var InstallerProfileReader
	 */
	protected $profile;
	public $error = array();
	public $installed = false;
	protected $dbConnection;

	protected $dbDebug = false;

	public function __construct(InstallerProfileReader $profile)
	{
		$this->profile = $profile;
	}

	public function install()
	{
		if(INSTALLMODE !== true)
			exit();

		$config = Config::getInstance();
		$input = Input::getInput();

		try{
			// Step 0 - Check for php requirements

			//0a Check for .blockinstall
			if(!$this->checkRequirements())
			{
				throw new Exception('Installation Already Present', 0);
			}

			// Step 2 - Present Paths, Databases and Options
			if(!isset($input['setup_location_root_Site_name']))
			{
				return false;
			}else{
			// Step 2 - Present Paths, Databases and Options
				if(!$this->saveConfiguration())
					throw new Exception('Error Setting Configuration File', 1);

				if(!$this->saveDatabaseConfiguration())
					throw new Exception('Supplied Database Information Invalid', 2);

				if(!$this->installDatabaseStructure())
					throw new Exception('Unable to load database structure. Please check permissions and that the
							database is clear of existing tables.', 3);

				if(!$this->setupStructure())
					throw new Exception('Error setting up base structure', 4);

				$this->installed = true;
				return true;
			}

		}catch (Exception $e){

			$this->installed = false;
			// step back through the program undoing everything up to the number
			switch ($e->getCode()) {
				case 5: // data
				case 4: // structure
				case 3: // database
					if($input['blowoutDataase'])
					{
						$config = Config::getInstance();
						$pathToSQL = $config['path']['modules'] . 'Installer/sql/system_remove.sql';
						$db = dbConnect('default');

						if(!$this->dbDebug)
							$db->runFile($pathToSQL);
					}
				case 2: // database files
					unlink($config['path']['base'] . 'data/configuration/databases.php');
				case 1: // configuration files
					unlink($config['path']['base'] . 'data/configuration/configuration.php');
				default:
					break;
			}

			$this->error[] = $e->getMessage();
			return false;
		}

	}

	protected function checkRequirements()
	{
		try{
			$config = Config::getInstance();
			if(file_exists($config['path']['base'] . '.blockinstall'))
				throw new Exception('blockinstall file found.');

			if(file_exists($config['path']['base'] . 'data/configuration/main_config.php'))
				throw new Exception('Configuration file already exists');

		}catch (Exception $e){
			return false;
		}
		return true;
	}

	protected function saveConfiguration()
	{
		try{
			$config = Config::getInstance();
			$input = Input::getInput();

			// Check paths
			$path['base'] = ($input['base']) ? $input['base'] : $config['path']['base'];
			$path['base'] = rtrim(trim($path['base']), '/') . '/';
			$path['theme'] = ($input['theme']) ? $input['theme'] : $path['base'] . 'data/themes/';
			$path['icons'] = ($input['icons']) ? $input['icons'] : $path['base'] . 'data/icons/';
			$path['fonts'] = ($input['fonts']) ? $input['fonts'] : $path['base'] . 'data/fonts/';
			$path['config'] = ($input['config']) ? $input['config'] : $path['base'] . 'data/configuration/';
			$path['mainClasses'] = ($input['mainClasses']) ? $input['mainClasses'] : $path['base'] . 'system/classes/';
			$path['packages'] = ($input['packages']) ? $input['packages'] : $path['base'] . 'modules/';
			$path['abstracts'] = ($input['abstracts']) ? $input['abstracts'] : $path['base'] . 'system/abstracts/';
			$path['engines'] = ($input['engines']) ? $input['engines'] : $path['base'] . 'system/engines/';
			$path['temp'] = ($input['tempPath']) ? $input['tempPath'] : $path['base'] . 'temp/';
			$path['library'] = ($input['library']) ? $input['library'] : $path['base'] . 'system/library/';
			$path['functions'] = ($input['functions']) ? $input['functions'] : $path['base'] . 'system/functions/';
			$path['javascript'] = ($input['javascript']) ? $input['javascript'] : $path['base'] . 'javascript/';
			$path['interfaces'] = ($input['interfaces']) ? $input['interfaces'] : $path['base'] . 'system/interfaces/';
			$path['templates'] = ($input['templates']) ? $input['templates'] : $path['base'] . 'system/templates/';
			$path['views'] = ($input['templates']) ? $input['templates'] : $path['base'] . 'system/views/';
			$path['thirdparty'] = ($input['thirdparty']) ? $input['thirdparty'] : $path['base'] . 'system/thirdparty/';


			$url['theme'] = 'data/themes/';
			$url['icons'] = 'data/icons/';
			$url['fonts'] = 'data/fonts/';
			$url['modules'] = 'bin/modules/';
			$url['javascript'] = 'javascript/';
			$url['modRewrite'] = (isset($input['url_modRewrite']));

			$imezone = (isset($input['system_timezone'])) ? $input['system_timezone'] : 'UTC';


			$cache = ($input['cacheHandler']) ? $input['cacheHandler'] : 'FileHandler'; // string, not boolean

			// Write Config File
			$directory = $config['path']['base'] . 'data/configuration/';
			if(is_writable($directory) && !file_exists($directory . 'configuration.php'))
			{
				$configFile = new ConfigFile($directory . 'configuration.php');
				$configFile->set('path', 'base', $path['base']);
				$configFile->set('path', 'theme', $path['theme']);
				$configFile->set('path', 'icons', $path['icons']);
				$configFile->set('path', 'fonts', $path['fonts']);
				$configFile->set('path', 'config', $path['config']);
				$configFile->set('path', 'mainclasses', $path['mainClasses']);
				$configFile->set('path', 'modules', $path['packages']);
				$configFile->set('path', 'abstracts', $path['abstracts']);
				$configFile->set('path', 'engines', $path['engines']);
				$configFile->set('path', 'temp', $path['temp']);
				$configFile->set('path', 'library', $path['library']);
				$configFile->set('path', 'functions', $path['functions']);
				$configFile->set('path', 'javascript', $path['javascript']);
				$configFile->set('path', 'interfaces', $path['interfaces']);
				$configFile->set('path', 'templates', $path['templates']);
				$configFile->set('path', 'views', $path['views']);
				$configFile->set('path', 'thirdparty', $path['thirdparty']);

				$configFile->set('url', 'theme', $url['theme']);
				$configFile->set('url', 'icons', $url['icons']);
				$configFile->set('url', 'fonts', $url['fonts']);
				$configFile->set('url', 'modules', $url['modules']);
				$configFile->set('url', 'javascript', $url['javascript']);

				$configFile->set('url', 'modRewrite', $url['modRewrite']);

				$configFile->set('system', 'cacheHandler', $cache);
				$configFile->set('system', 'timezone', $imezone);
				$configFile->write();

			}else{
				throw new CoreError('Configuration failed to save');
			}
			$config->reset();
		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function saveDatabaseConfiguration()
	{
		try{
			// Check Database Connections
			$config = Config::getInstance();
			$input = Input::getInput();

			$directory = $config['path']['base'] . 'data/configuration/';
			$dbIniFile = new ConfigFile($directory . 'databases.php');

			if(!isset($input['DBhost']) || !isset($input['DBusername']) || !isset($input['DBpassword'])
				|| !isset($input['DBname']))
					throw new Exception('No Database information', 1);

			if($connection = new mysqli($input['DBhost'], $input['DBusername'],
											$input['DBpassword'], $input['DBname']))
			{
				$dbIniFile->set('default', 'username', $input['DBusername']);
				$dbIniFile->set('default', 'password', $input['DBpassword']);
				$dbIniFile->set('default', 'host', $input['DBhost']);
				$dbIniFile->set('default', 'dbname', $input['DBname']);
				$this->dbConnection = $connection;
			}else{
				throw new Exception('Unable to select database with main user', 2);
			}

			if(isset($input['DBROhost'], $input['DBROusername'], $input['DBROpassword'], $input['DBROname'])
				&& (strlen($input['DBROname']) > 0
					|| strlen($input['DBROusername']) > 0
					|| strlen($input['DBROpassword']) > 0))
			{

				$ROconnection = mysqli_connect($input['DBROhost'], $input['DBROusername'],
									 $input['DBROpassword'], $input['DBROname']);

				if(!mysqli_connect_error())
				{
					$dbIniFile->set('default_read_only', 'username', $input['DBROusername']);
					$dbIniFile->set('default_read_only', 'password', $input['DBROpassword']);
					$dbIniFile->set('default_read_only', 'host', $input['DBROhost']);
					$dbIniFile->set('default_read_only', 'dbname', $input['DBROname']);
				}else{
					throw new Exception('Unable to select database with read only user', 4);
				}
			}else{
				$dbIniFile->set('default_read_only', 'username', $input['DBusername']);
				$dbIniFile->set('default_read_only', 'password', $input['DBpassword']);
				$dbIniFile->set('default_read_only', 'host', $input['DBhost']);
				$dbIniFile->set('default_read_only', 'dbname', $input['DBname']);
			}

			$dbIniFile->write();

		}catch(Exception $e){
			switch ($e->getCode())
			{
				case 1:
					$message = 'Please fill out all of the database information.';
					break;
				case 2:
					$message = 'There was an error with the primary database credentials supplied.';
					break;

				case 4:
					$message = 'There was an error with the read only database credentials supplied.';
					break;


				case 3:
					$message = 'Please make sure your database credentials and server are correct.';
					break;

				default:
					$message = 'There was an error setting up the database connections.';
			}
			$this->error[] = $message;
			// Error Writing Database Config Files
			return false;
		}

		return true;
	}

	protected function installDatabaseStructure()
	{
		try{
			$config = Config::getInstance();
			// Sanity check on previous data
			$config->reset();

			// Set Up database structure

			$db = dbConnect('default');
			$input = Input::getInput();

			$result = $db->query('SHOW TABLES');

			if($result && $result->num_rows > 0)
			{
				if($input['blowoutDatabase'])
				{
					$config = Config::getInstance();
					$pathToSQL = $config['path']['modules'] . 'Installer/sql/system_remove.sql';

					if(!$db->runFile($pathToSQL))
						return false;
				}else{
					return false;
				}
			}


			$pathToSQL = $config['path']['modules'] . 'Installer/sql/system_install.sql';

			if(!$db->runFile($pathToSQL))
			{
				throw new CoreError('Unable to load database structure');
			}

		}catch(Exception $e){
			return false;
		}
		return true;
	}


	protected function setupStructure()
	{
		$this->profile->getAliases();
		$this->profile->getMembergroups();
		$this->profile->getUsers();
		$this->profile->getModules();
		$this->profile->getLocations();

		$userLand = new InstallerSetupUserland($this->profile);


		return $userLand->setupCoreSystem();

	}
}

class InstallerSetupUserland
{
	/**
	 * Installations profile
	 *
	 * @var InstallerProfileReader
	 */
	protected $profile;

	protected $savedGroups;
	protected $savedUsers;

	public function __construct(InstallerProfileReader $profile)
	{
		$this->profile = $profile;
	}

	public function setupCoreSystem()
	{
		$this->savedGroups = $this->setupMembergroups($this->profile->getMembergroups());
		$this->savedUsers = $this->setupUsers($this->profile->getUsers());

		$location = $this->profile->getLocations();
		$rootLocation = $this->createLocationBase($location['root']);

		$this->setupModules($this->profile->getModules());

		$this->setupLocationFromProfile($rootLocation, $location['root']);

		$this->setupLocations($location['root']['children'], $rootLocation);
		return true;
	}

	protected function createLocationBase($locationInfo)
	{
		$locationRoot = new Location();
		$locationRoot->setName('root');
		$locationRoot->setResource('Root', '0');
		$locationRoot->setOwnerGroup($this->savedGroups['System']);
		$locationRoot->save();
		return $locationRoot;
	}

	protected function setupMembergroups($membergroups)
	{
		$savedMembergroups = array();
		$config = Config::getInstance();
		$input = Input::getInput();

		if(!class_exists('MortarModelMemberGroup', false))
			include($config['path']['modules'] . 'Mortar/models/MemberGroup.class.php');

		foreach($membergroups['system'] as $group)
		{
			$membergroup = new MortarModelMemberGroup();
			$membergroup['name'] = $group;
			$membergroup['is_system'] = 1;
			$membergroup->save();
			$savedMembergroups[$group] = $membergroup;
		}

		foreach($membergroups['user'] as $group)
		{
			$membergroup = new MortarModelMemberGroup();
			$membergroup['name'] = $group;
			$membergroup->save();
			$savedMembergroups[$group] = $membergroup;
		}
		return $savedMembergroups;
	}

	protected function setupUsers($userList)
	{
		$config = Config::getInstance();
		$input = Input::getInput();
		$membergroups = $this->savedGroups;

		if(!class_exists('MortarModelUser', false))
			include($config['path']['modules'] . 'Mortar/models/User.class.php');

		foreach($userList as $name => $user)
		{
			$newUser = new MortarModelUser();

			if($user['form'])
			{
				$inputBaseName = 'setup_user_' .$name . '_';

				if(isset($input[$inputBaseName . 'name']))
				{
					$newUser['name'] = $input[$inputBaseName . 'name'];
				}else{
					$newUser['name'] = $name;
				}

				if(isset($input[$inputBaseName . 'password']))
				{
					$newUser['password'] = $input[$inputBaseName . 'password'];
				}

				if(isset($input[$inputBaseName . 'email']))
				{
					$newUser['email'] = $input[$inputBaseName . 'email'];
				}


			}else{
				$newUser['name'] = $name;
			}

			$newUser['allowlogin'] = $user['login'];
			$newUser->save();

			foreach($user['groups'] as $group)
			{
				if(!isset($membergroups[$group]))
					throw new CoreError('Unable to add user to nonexistant group ' . $group);

				$membergroups[$group]->addUser($newUser);
				//$membergroups[$group]->save();
			}
		}
	}

	protected function setupModules($moduleList)
	{
		$config = Config::getInstance();
		if(!class_exists('ModuleInstaller', false))
				include($config['path']['mainclasses'] . 'ModuleInstaller.class.php');

		foreach($moduleList as $moduleName => $moduleInfo)
		{
			if($moduleInfo['install'] !== true)
				continue;

			$customInstallerName = 'moduleInstall' . $moduleName;
			$path = $config['path']['modules'] . 'classes/hooks/moduleInstaller.class.php';

			if(!class_exists($customInstallerName, false) && file_exists($path))
				include($path);

			$class = (class_exists($customInstallerName, false)) ? $customInstallerName : 'ModuleInstaller';

			$installation = new $class($moduleName);
			if(!$installation->fullInstall())
				throw new CoreError('Module ' . $moduleName . ' Installation failed.');
		}

		return true;
	}

	protected function setupLocations($locations, Location $parent = null)
	{
		$input = Input::getInput();
		foreach($locations as $locationName => $locationInfo)
		{
			$inputBaseName = 'setup_location_' . $locationInfo['longname'] . '_';
			if($locationInfo['form'] && isset($input[$inputBaseName . 'name']))
				$locationName = $input[$inputBaseName . 'name'];

			if(!isset($locationInfo['id']))
			{
				$model = ModelRegistry::loadModel($locationInfo['type']);
				$model->name = $locationName;

				if(isset($locationInfo['content']) || isset($locationInfo['property']))
				{
					if(isset($locationInfo['content']))
						foreach($locationInfo['content'] as $name => $value)
							$model[$name] = $value;

					if(isset($locationInfo['property']))
						foreach($locationInfo['property'] as $name => $value)
							$model->$name = $value;
				}

				$model->save();

				if(isset($locationInfo['functions']))
					foreach($locationInfo['functions'] as $function)
					{
						$functionName = $function['name'];

						$passParameters = array();

						if(isset($function['params']))
							foreach($function['params'] as $index => $parameter)
							{
								$inputName = $inputBaseName . 'model_functions_' . $functionName . '_' . $index;

								if($parameter['form'] && isset($_POST[$inputName]))
								{
									$value = $_POST[$inputName];
								}else{
									$value = $parameter['value'];
								}

								$inputBaseName . 'model_functions_' . $functionName . '_' . $index;
								$passParameters[] = $value;
							}

						call_user_func_array(array($model, $functionName), $passParameters);
					}

				$location = $model->getLocation();
			}else{
				$location = new Location();
				$location->setName($locationName);
				$location->setResource($locationInfo['type'], $locationInfo['id']);
			}

			if(isset($parent))
				$location->setParent($parent);


			// continue setting things up from the XML profile
			$this->setupLocationFromProfile($location, $locationInfo);

			if(isset($locationInfo['children']))
				$this->setupLocations($locationInfo['children'], $location);
		}
	}


	protected function setupLocationFromProfile(Location $location, $locationInfo)
	{
		$inputBaseName = 'setup_location_' . $locationInfo['longname'] . '_';

		$users = $this->savedUsers;
		$groups = $this->savedGroups;

		if(isset($locationInfo['options']))
			foreach($locationInfo['options'] as $name => $value)
			{
				$value = ($locationInfo['form'] && isset($input[$inputBaseName . 'option_' . $name]))
									? $input[$inputBaseName . 'name'] : $value;

				$location->setMeta($name, $value);
			}

		$location->setInherit($locationInfo['inherits']);

		if(isset($locationInfo['owner']))
			$location->setOwner($users[$locationInfo['owner']]->getId());

		if(isset($locationInfo['group']))
			$location->getOwnerGroup($groups[$locationInfo['group']]->getId());


		$location->save();
		$locationId = $location->getId();

		if(isset($locationInfo['permissions']))
		{
			$aliases = $this->getAliasesInternal();

			// run through each set of permissions
			foreach($locationInfo['permissions'] as $permissionInfo)
			{
				$permissions = array();

				// add user permissions
				if(isset($locationInfo['users']))
					foreach($locationInfo['users'] as $user)
						$permissions[] = new UserPermission($locationId, $users[$user]->getId());

				// add group permissions
				if(isset($permissionInfo['groups']))
					foreach($permissionInfo['groups'] as $group)
						$permissions[] = new GroupPermission($locationId, $groups[$group]->getId());

				if(count($permissions) < 1)
					continue;

				// Replace aliases with real values
				$aliasTypes = array('modelGroups' => 'resources', 'actionGroups' => 'actions');
				foreach($aliasTypes as $aliasGroup => $realGroup)
				{
					foreach($aliases[$aliasGroup] as $aliasName => $realMembers)
					{
						if(in_array($aliasName, $permissionInfo[$realGroup]))
						{
							$key = array_search($aliasName, $permissionInfo[$realGroup]);
							unset($permissionInfo[$realGroup][$key]);

							$permissionInfo[$realGroup] = array_merge($permissionInfo[$realGroup], $realMembers);
						}
					}
					$permissionInfo[$realGroup] = array_unique($permissionInfo[$realGroup]);
				}

				// Add all of the models and their actions to the permission system
				foreach($permissionInfo['resources'] as $resource)
				{
					foreach($permissionInfo['actions'] as $action)
					{
						if(!($actionId = PermissionActionList::getAction($action)))
						{
							PermissionActionList::addAction($action);
							$actionId = PermissionActionList::getAction($action);
						}

						foreach($permissions as $permission)
							$permission->setPermission($resource, $actionId);
					}
				}

				// save each of the permissions
				foreach($permissions as $permission)
					$permission->save();
			}
		}//if(isset($locationInfo['permissions']))

		return true;
	}

	protected function getAliasesInternal()
	{
		$aliases = $this->profile->getAliases();

		$allActions = $aliases['actionGroups'];
		$allActions[] = PermissionActionList::getActionList();
		$aliases['actionGroups']['All'] = array_unique(call_user_func_array('array_merge', $allActions));

		$allModels = $aliases['modelGroups'];
		$allModels[] = ModelRegistry::getModelList();
		//var_dump($allModels);
		$aliases['modelGroups']['All'] = array_unique(call_user_func_array('array_merge', $allModels));
		$aliases['modelGroups']['All'][] = 'Base';

		if($key = array_search('Universal', $aliases['modelGroups']['All']))
			unset($aliases['modelGroups']['All'][$key]);

		return $aliases;
	}
}

?>
