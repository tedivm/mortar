<?php

class BentoBaseInstaller
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
					throw new Exception('Unable to load database structure.', 3);

				if(!$this->setupStructure())
					throw new Exception('Error setting up base structure', 4);


		//		if(!$this->setupCoreModule())
		//			throw new Exception('Error installing Core module.', 5);


				file_put_contents($config['path_base'] . '.blockinstall', 'To unblock installation, delete this file.');
				$this->installed = true;
				return true;
			}

		}catch (Exception $e){

			$this->installed = false;
			$this->error[] = $e->getMessage();
			// step back through the program undoing everything up to the number
			switch ($e->getCode()) {
				case 5: // data
				case 4: // structure
				case 3: // database
					$config = Config::getInstance();
					$pathToSQL = $config['path']['modules'] . 'BentoBase/sql/system_remove.sql.php';
					$db = dbConnect('default');

					if(!$this->dbDebug)
						$db->runFile($pathToSQL);
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

			$url['theme'] = 'data/themes/';
			$url['modules'] = 'bin/modules/';
			$url['javascript'] = 'javascript/';
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
				$configFile->set('url', 'theme', $url['theme']);
				$configFile->set('url', 'modules', $url['modules']);
				$configFile->set('url', 'javascript', $url['javascript']);
				$configFile->set('cache', 'handler', $cache);
				$configFile->write();

			}else{
				throw new BentoError('Configuration filed to save');
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

			if(!$input['DBhost'] || !$input['DBusername'] || !$input['DBpassword'] || !$input['DBname'])
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
				throw new Exception('Unable to select database', 2);
			}

			if(($input['DBROhost'] && $input['DBROusername'] && $input['DBROpassword'] && $input['DBROname'])
				&& ($ROconnection = mysqli_connect($input['DBROhost'], $input['DBROusername'],
												 $input['DBROpassword'], $input['DBROname'])))
			{
				$dbIniFile->set('default_read_only', 'username', $input['DBROusername']);
				$dbIniFile->set('default_read_only', 'password', $input['DBROpassword']);
				$dbIniFile->set('default_read_only', 'host', $input['DBROhost']);
				$dbIniFile->set('default_read_only', 'dbname', $input['DBROname']);

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
					$message = 'Please make sure your database name is correct.';
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
			$pathToSQL = $config['path']['modules'] . 'BentoBase/sql/system_install.sql.php';

			$db = dbConnect('default');

			if(!$db->runFile($pathToSQL))
			{
				throw new BentoError('Unable to load database structure');
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

			// CREATE USERS
			if(!class_exists('User', false))
			{
				include($config['path']['mainclasses'] . 'user.class.php');
			}

			$userAdmin = new User();
			$userAdmin->setName($input['username']);
			$userAdmin->setPassword($input['password']);
			$userAdmin->save();

			$userGuest = new User();
			$userGuest->setName('guest');
			$userGuest->save();

			$userSystem = new User();
			$userSystem->setName('system');
			$userSystem->save();

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


			// ADD USERS TO MEMBERGROUPS
			$memgroupAdmin->addUser($userAdmin);
			$memgroupUser->addUser($userAdmin);
			$memgroupGuest->addUser($userGuest);

			// Root Users (these guys can do anything)
			$memgroupSuperUser->addUser($userAdmin);
			$memgroupSuperUser->addUser($userSystem);


			// Make the active user the system
			$activeUser = ActiveUser::getInstance();
			$activeUser->loadUser($userSystem->getId());


			// CREATE ROOT LOCATION

			// The root location causes a chicken and egg problem- we can't create register the first model without
			// installing its module, but we can't install the module without the first location.
			// So we just force it to start the system.
			$locationRoot = new Location();
			$locationRoot->setName('root');
			$locationRoot->setResource('Root', '0');

			$locationRoot->setMeta('adminTheme', 'admin');
			$locationRoot->setMeta('htmlTheme', 'default');

			$locationRoot->save();






			if(!$this->setupCoreModule())
				return false;



			// Create Site

			$site = new BentoBaseModelSite();
			$site->name = $input['siteName'];
			$site['allowIndex'] = 1;
			$site->save($locationRoot);

			$ssl = isset($input['ssl'][0]);
			$site->addUrl($input['domain'], $ssl, true);

			$siteLocation = $site->getLocation();



			$membersOnlyDirectory = new BentoBaseModelDirectory();
			$membersOnlyDirectory->name = 'MembersOnly';
			$membersOnlyDirectory['allowIndex'] = 1;
			$membersOnlyDirectory->save($siteLocation);

			$locationMembersOnly = $membersOnlyDirectory->getLocation();
			$locationMembersOnly->setInherit(false);
			$locationMembersOnly->save();


			$adminOnlyDirectory = new BentoBaseModelDirectory();
			$adminOnlyDirectory->name = 'AdminOnly';
			$adminOnlyDirectory['allowIndex'] = 1;
			$adminOnlyDirectory->save($siteLocation);
			$locationAdminOnly = $adminOnlyDirectory->getLocation();
			$locationAdminOnly->setInherit(false);
			$locationAdminOnly->save();


			$page = new BentoCMSModelPage();
			$page->name = 'home';
			$page['title'] = 'Welcome to BentoBase';
			$page['content'] = 'BentoBase- default installation text coming soon!';
			$page->save($siteLocation);
			$pageLocation = $page->getLocation();


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
			$defaultModules = array ('default' => 'BentoBase', 'error' => 'BentoBotch');

			foreach($defaultModules as $name => $package)
			{
				if($this->installModule($package))
					$rootLocation->setMeta($name, $package);
			}
			$rootLocation->save();

			$this->installModule('BentoCMS');

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