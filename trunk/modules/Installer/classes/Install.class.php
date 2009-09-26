<?php

class InstallerInstaller // thats the most pathetic name ever
{
	public $error = array();
	public $installed = false;
	protected $dbConnection;

	protected $dbDebug = false;

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
			if(!isset($input['siteName']))
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
			$path['thirdparty'] = ($input['thirdparty']) ? $input['thirdparty'] : $path['base'] . 'system/thirdparty/';


			$url['theme'] = 'data/themes/';
			$url['modules'] = 'bin/modules/';
			$url['javascript'] = 'javascript/';
			$url['modRewrite'] = (isset($input['url_modRewrite']));

			$imezone = (isset($input['system_timezone'])) ? $input['system_timezone'] : 'UTC';


			$cache = ($input['cacheHandler']) ? $input['cacheHandler'] : 'FileHandler'; // string, not boolean

			// Write Config File
			$directory = $config['path']['base'] . 'data/configuration/';
			if(is_writable($directory) && !file_exists($directory . 'configuration.php'))
			{
				$configFile = new IniFile($directory . 'configuration.php');
				$configFile->set('path', 'base', $path['base']);
				$configFile->set('path', 'theme', $path['theme']);
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
				$configFile->set('path', 'thirdparty', $path['thirdparty']);

				$configFile->set('url', 'theme', $url['theme']);
				$configFile->set('url', 'modules', $url['modules']);
				$configFile->set('url', 'javascript', $url['javascript']);

				$configFile->set('url', 'modRewrite', $url['modRewrite']);

				$configFile->set('system', 'cacheHandler', $cache);
				$configFile->set('system', 'timezone', $imezone);
				$configFile->write();

			}else{
				throw new CoreError('Configuration filed to save');
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
			$dbIniFile = new IniFile($directory . 'databases.php');

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
		try{
			$config = Config::getInstance();
			$input = Input::getInput();

			if(!class_exists('User', false))
			{
				include($config['path']['modules'] . 'Mortar/models/User.class.php');
			}


			$userAdmin = new MortarModelUser();
			$userAdmin['name'] = $input['username'];
			$userAdmin['password'] = $input['password'];
			$userAdmin->save();

			$userGuest = new MortarModelUser();
			$userGuest['name'] = 'Guest';
			$userGuest->save();

			$userSystem = new MortarModelUser();
			$userSystem['name'] = 'System';
			$userSystem->save();


			$userCron = new MortarModelUser();
			$userCron['name'] = 'Cron';
			$userCron->save();

			// CREATE MEMBERGROUPS


			$memgroupAdmin = new MemberGroup();
			$memgroupAdmin->setName('Administrator');
			$memgroupAdmin->save();



			$memgroupUser = new MemberGroup();
			$memgroupUser->setName('User');
			$memgroupUser->save();


			$memgroupGuest = new MemberGroup();
			$memgroupGuest->setName('Guest');
			$memgroupGuest->makeSystem();
			$memgroupGuest->save();

			$memgroupResourceOwner = new MemberGroup();
			$memgroupResourceOwner->setName('ResourceOwner');
			$memgroupResourceOwner->makeSystem();
			$memgroupResourceOwner->save();

			$memgroupResourceGroupOwner = new MemberGroup();
			$memgroupResourceGroupOwner->setName('ResourceGroupOwner');
			$memgroupResourceGroupOwner->makeSystem();
			$memgroupResourceGroupOwner->save();

			$memgroupSuperUser = new MemberGroup();
			$memgroupSuperUser->setName('SuperUser');
			$memgroupSuperUser->makeSystem();
			$memgroupSuperUser->save();


			$memgroupSystem = new MemberGroup();
			$memgroupSystem->setName('System');
			$memgroupSystem->makeSystem();
			$memgroupSystem->save();

			// ADD USERS TO MEMBERGROUPS
			$memgroupAdmin->addUser($userAdmin);
			$memgroupUser->addUser($userAdmin);
			$memgroupGuest->addUser($userGuest);

			// Root Users (these guys can do anything)
			$memgroupSuperUser->addUser($userAdmin);
			$memgroupSuperUser->addUser($userSystem);
			$memgroupSuperUser->addUser($userCron);


			// CREATE ROOT LOCATION

			// The root location causes a chicken and egg problem- we can't create register the first model without
			// installing its module, but we can't install the module without the first location.
			// So we just force it to start the system.
			$locationRoot = new Location();
			$locationRoot->setName('root');
			$locationRoot->setResource('Root', '0');
			$locationRoot->setOwnerGroup($memgroupSystem);
			$locationRoot->setMeta('adminTheme', 'bbAdmin');
			$locationRoot->setMeta('htmlTheme', 'default');

			$locationRoot->save();

			$locationTrash = new Location();
			$locationTrash->setName('Trash');
			$locationTrash->setResource('TrashCan', '0');
			$locationTrash->setParent($locationRoot);
			$locationTrash->save();




			if(!$this->setupCoreModule())
				return false;


			// Make the active user the system
			ActiveUser::changeUserById($userSystem->getId());


			// Create Site

			$site = new MortarModelSite();
			$site->name = $input['siteName'];
			$site['allowIndex'] = 1;
			$site->setParent($locationRoot);
			$site->save();

			$ssl = isset($input['ssl'][0]);
			$site->addUrl($input['domain'], $ssl, true);
			$site->addUrl('default');
			$siteLocation = $site->getLocation();



			$membersOnlyDirectory = new MortarModelDirectory();
			$membersOnlyDirectory->name = 'MembersOnly';
			$membersOnlyDirectory['allowIndex'] = 1;
			$membersOnlyDirectory->setParent($siteLocation);
			$membersOnlyDirectory->save();

			$locationMembersOnly = $membersOnlyDirectory->getLocation();
			$locationMembersOnly->setInherit(false);
			$locationMembersOnly->setOwnerGroup($memgroupSystem);
			$locationMembersOnly->save();


			$adminOnlyDirectory = new MortarModelDirectory();
			$adminOnlyDirectory->name = 'AdminOnly';
			$adminOnlyDirectory['allowIndex'] = 1;
			$adminOnlyDirectory->setParent($siteLocation);
			$adminOnlyDirectory->save();

			$locationAdminOnly = $adminOnlyDirectory->getLocation();
			$locationAdminOnly->setInherit(false);
			$locationAdminOnly->setOwnerGroup($memgroupSystem);
			$locationAdminOnly->save();


			$page = new LithoModelPage();
			$page->name = 'index';
			$page['title'] = 'Welcome to Mortar';
			$page['content'] = 'Mortar- default installation text coming soon!';
			$page->setParent($siteLocation);
			$page->save();
			$pageLocation = $page->getLocation();
			$pageLocation->setOwnerGroup($memgroupSystem);
			$pageLocation->save();
			$site['defaultChild'] = $pageLocation->getId();
			$site->save();




			// Add Admin permissions


			ModelRegistry::clearHandlers();
			$coreResources = ModelRegistry::getModelList();
			$coreResources[] = 'Base';
			$corePermissions = array('Read', 'Edit', 'Add', 'Execute', 'System', 'Admin');

			$adminResources = $coreResources;
			$adminResources[] = 'Universal';



			$adminRootPermissions = new GroupPermission($memgroupAdmin->getId(), $locationRoot->getId());
			$adminOnlyPermissions = new GroupPermission($memgroupAdmin->getId(), $locationAdminOnly->getId());
			$adminMembersPermissions = new GroupPermission($memgroupAdmin->getId(), $locationMembersOnly->getId());

			foreach($corePermissions as $permission)
			{
				// Register action type
				PermissionActionList::addAction($permission);
				$permissionId = PermissionActionList::getAction($permission);

				// loop through resources to add permissions for eachs
				foreach($adminResources as $resource)
				{
					   $adminRootPermissions->setPermission($resource, $permissionId, true);
					   $adminOnlyPermissions->setPermission($resource, $permissionId, true);
					$adminMembersPermissions->setPermission($resource, $permissionId, true);
				}

			}

			$adminRootPermissions->save();
			$adminOnlyPermissions->save();
			$adminMembersPermissions->save();

			// Add user permissions
			$userMembersPermissions = new GroupPermission($memgroupUser->getId(), $locationMembersOnly->getId());
			$userSitePermissions = new GroupPermission($memgroupUser->getId(), $siteLocation->getId());
			$guestSitePermissions = new GroupPermission($memgroupGuest->getId(), $siteLocation->getId());


			$restrictedObjects = array('Root');
			$permissionId = PermissionActionList::getAction('Read');
			foreach($coreResources as $resource)
			{
				if(in_array($resource, $restrictedObjects))
					continue;

				$userMembersPermissions->setPermission($resource, $permissionId, true);
				$userSitePermissions->setPermission($resource, $permissionId, true);
				$guestSitePermissions->setPermission($resource, $permissionId, true);
			}

			$userMembersPermissions->save();
			$userSitePermissions->save();
			$guestSitePermissions->save();


		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function setupCoreModule()
	{
		try{
			$rootLocation = new Location(1);
			$defaultModules = array ('default' => 'Mortar', 'error' => 'Rubble');

			foreach($defaultModules as $name => $package)
			{
				if($this->installModule($package))
					$rootLocation->setMeta($name, $package);
			}
			$rootLocation->save();

			$this->installModule('Litho');

		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function installModule($moduleName)
	{
		$config = Config::getInstance();
		if(!class_exists('ModuleInstaller', false))
				include($config['path']['mainclasses'] . 'ModuleInstaller.class.php');

		$customInstallerName = 'moduleInstall' . $moduleName;
		$path = $config['path']['modules'] . 'classes/hooks/moduleInstaller.class.php';

		if(!class_exists($customInstallerName, false) && file_exists($path))
			include($path);

		$class = (class_exists($customInstallerName, false)) ? $customInstallerName : 'ModuleInstaller';

		$installation = new $class($moduleName);
		if(!$installation->fullInstall())
			throw new Exception('Module Installation failed.');

		return true;
	}
}

?>