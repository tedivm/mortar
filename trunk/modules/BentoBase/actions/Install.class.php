<?php

class BentoBaseActionInstall //extends Action
{

	protected $form = true;
	protected $error = array();
	protected $installed = false;
	protected $dbConnection;
	public $subtitle = '';

	public function __construct()
	{
		$config = Config::getInstance();
		try{
			// Step 0 - Check for php requirements

			//0a Check for .blockinstall
			if(!$this->checkRequirements())
			{
				$this->form = false;
				throw new Exception('Installation Already Present', 0);
			}

			$post = Post::getInstance();
			// Step 2 - Present Paths, Databases and Options
			if(!isset($post['siteName']))
			{
				$this->form = true;
				return;

			}else{
			// Step 2 - Present Paths, Databases and Options
				if(!$this->saveConfiguration())
					throw new Exception('Error Setting Configuration File', 1);

				if(!$this->saveDatabaseConfiguration())
					throw new Exception('Supplied Database Information Invalid', 2);

				if(!$this->installDatabaseStructure())
					throw new Exception('Unable to load database structure.', 3);

				if(!$this->setupStructure())
					throw new Exception('Error setting up permissions', 4);

				if(!$this->setupCoreModule())
					throw new Exception('Error installing Core module.', 5);


				file_put_contents($config['path_base'] . '.blockinstall', 'To unblock installation, delete this file.');
				$this->installed = true;
			}

		}catch (Exception $e){

			$this->installed = false;
			$this->error[] = $e->getMessage();
			// step back through the program undoing everything up to the number
			switch ($e->getCode()) {
				case 5: //module
				case 4: //permissions
				case 3: // database
					$config = Config::getInstance();
					$pathToSQL = $config['path']['modules'] . 'BentoBase/sql/system_remove.sql.php';
					$db = dbConnect('default');
					$db->runFile($pathToSQL);
				case 2: // database files
					unlink($config['path']['base'] . 'data/configuration/databases.php');
				case 1: // configuration files
					unlink($config['path']['base'] . 'data/configuration/configuration.php');
				default:
					break;
			}

			$this->error[] = $e->getMessage();

		}

	}

	public function viewInstall()
	{
		$config = Config::getInstance();
		$output = '';

		$modulePath = $config['path']['modules'] . 'BentoBase/';

		foreach($this->error as $errorMessage)
		{
			$output .= '<div id="error" class="error">' . $errorMessage . '</div>' . PHP_EOL;
		}

		if($this->installed)
		{
			$output .= file_get_contents($modulePath . 'templates/installationComplete.template.php');
			$this->subtitle = 'Installation Complete';
		}elseif($this->form){
			include($modulePath . 'classes/InstallationForm.class.php');
			$form = new InstallationForm('Installation');
			$output .= $form->makeDisplay();
		}
		return $output;
	}

	protected function checkRequirements()
	{
		try{
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
			// Check paths
			$path['base'] = ($_POST['base']) ? $_POST['base'] : $config['path']['base'];
			$path['base'] = rtrim(trim($path['base']), '/') . '/';
			$path['theme'] = ($_POST['theme']) ? $_POST['theme'] : $path['base'] . 'data/themes/';
			$path['config'] = ($_POST['config']) ? $_POST['config'] : $path['base'] . 'data/configuration/';
			$path['mainClasses'] = ($_POST['mainClasses']) ? $_POST['mainClasses'] : $path['base'] . 'system/classes/';
			$path['packages'] = ($_POST['packages']) ? $_POST['packages'] : $path['base'] . 'modules/';
			$path['abstracts'] = ($_POST['abstracts']) ? $_POST['abstracts'] : $path['base'] . 'system/abstracts/';
			$path['engines'] = ($_POST['engines']) ? $_POST['engines'] : $path['base'] . 'system/engines/';
			$path['temp'] = ($_POST['tempPath']) ? $_POST['tempPath'] : $path['base'] . 'temp/';
			$path['library'] = ($_POST['library']) ? $_POST['library'] : $path['base'] . 'system/library/';
			$path['functions'] = ($_POST['functions']) ? $_POST['functions'] : $path['base'] . 'system/functions/';
			$path['javascript'] = ($_POST['javascript']) ? $_POST['javascript'] : $path['base'] . 'javascript/';
			$path['interfaces'] = ($_POST['interfaces']) ? $_POST['interfaces'] : $path['base'] . 'interfaces/';

			$url['theme'] = 'data/themes/';
			$url['modules'] = 'bin/modules/';
			$url['javascript'] = 'javascript/';
			$cache = ($_POST['cacheHandler']) ? $_POST['cacheHandler'] : 'FileHandler'; // string, not boolean

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
			$directory = $config['path']['base'] . 'data/configuration/';
			$dbIniFile = new IniFile($directory . 'databases.php');

			if(!$_POST['DBhost'] || !$_POST['DBusername'] || !$_POST['DBpassword'] || !$_POST['DBname'])
				throw new Exception('No Database information', 1);

			if($connection = new mysqli($_POST['DBhost'], $_POST['DBusername'], $_POST['DBpassword'], $_POST['DBname']))
			{
				$dbIniFile->set('default', 'username', $_POST['DBusername']);
				$dbIniFile->set('default', 'password', $_POST['DBpassword']);
				$dbIniFile->set('default', 'host', $_POST['DBhost']);
				$dbIniFile->set('default', 'dbname', $_POST['DBname']);
				$this->dbConnection = $connection;
			}else{
				throw new Exception('Unable to select database', 2);
			}

			if(($_POST['DBROhost'] && $_POST['DBROusername'] && $_POST['DBROpassword'] && $_POST['DBROname'])
				&& ($ROconnection = mysqli_connect($_POST['DBROhost'], $_POST['DBROusername'], $_POST['DBROpassword'], $_POST['DBROname'])))
			{
				$dbIniFile->set('default_read_only', 'username', $_POST['DBROusername']);
				$dbIniFile->set('default_read_only', 'password', $_POST['DBROpassword']);
				$dbIniFile->set('default_read_only', 'host', $_POST['DBROhost']);
				$dbIniFile->set('default_read_only', 'dbname', $_POST['DBROname']);

			}else{
				$dbIniFile->set('default_read_only', 'username', $_POST['DBusername']);
				$dbIniFile->set('default_read_only', 'password', $_POST['DBpassword']);
				$dbIniFile->set('default_read_only', 'host', $_POST['DBhost']);
				$dbIniFile->set('default_read_only', 'dbname', $_POST['DBname']);
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

			// CREATE USERS
			if(!class_exists('ManageUser', false))
			{
				include($config['path']['mainclasses'] . 'permissions_editing.class.php');
			}

			$user_admin = new ManageUser();
			$user_admin->user_name = $_POST['username'];
			$user_admin->user_password = $_POST['password'];
			$user_admin->allow_login = true;
			$user_admin->save();

			$user_guest = new ManageUser();
			$user_guest->user_name = 'guest';
			$user_guest->save();

			// CREATE MEMBERGROUPS
			$memgroup_admin = new MemberGroup();
			$memgroup_admin->setName('SuperUser');
			$memgroup_admin->save();

			$memgroup_admin = new MemberGroup();
			$memgroup_admin->setName('Administrator');
			$memgroup_admin->save();

			$memgroup_guest = new MemberGroup();
			$memgroup_guest->setName('Guest');
			$memgroup_guest->save();

			$memgroup_user = new MemberGroup();
			$memgroup_user->setName('User');
			$memgroup_user->save();

			// ADD USERS TO MEMBERGROUPS
			$memgroup_admin->addUser($user_admin->user_id);
			$memgroup_user->addUser($user_admin->user_id);
			$memgroup_guest->addUser($user_guest->user_id);

			// CREATE ROOT LOCATION
			$location_root = new Location();
			$location_root->name = 'root';
			$location_root->resource = 'directory';
			$location_root->meta = array('adminTheme' => 'admin', 'htmlTheme' => 'default');
			$location_root->save();

			// CREATE SITE
			$location_site = new Location();
			$location_site->name = $_POST['siteName'];
			$location_site->resource = 'site';
			$location_site->parent = $location_root;
			$location_site->save();


			$site = new ObjectRelationshipMapper('sites');
			$site->location_id = $location_site->location_id();
			$site->name = $_POST['siteName'];
			$site->save();

			$primaryDomain = ($_POST['domain']) ? rtrim(trim($_POST['domain']), '/') . '/' : '';

			if(strpos($primaryDomain, 'http://') !== false)
			{
				$primaryDomain = substr($primaryDomain, strpos($url['domain'], 'http://') + 7);
			}

			if($primaryDomain != '')
			{
				$primaryDomainRecord = new ObjectRelationshipMapper('urls');
				$primaryDomainRecord->site_id = $site->site_id;
				$primaryDomainRecord->urlPath = $primaryDomain;
				$primaryDomainRecord->save();
			}

			$sslDomain = ($_POST['ssl']) ? rtrim(trim($_POST['ssl']), '/') . '/' : '';

			if($sslDomain != '' && (strpos($sslDomain, 'https://') !== false))
			{
				$sslDomain = substr($sslDomain, strpos($sslDomain, 'https://') + 8);
			}

			if($sslDomain != '')
			{
				$sslDomainRecord = new ObjectRelationshipMapper('urls');
				$sslDomainRecord->site_id = $site->site_id;
				$sslDomainRecord->urlPath = $sslDomain;
				$sslDomainRecord->urlSSL = '1';
				$sslDomainRecord->save();
			}

			$location_membersonly = new Location();
			$location_membersonly->name = 'members_only';
			$location_membersonly->resource = 'directory';
			$location_membersonly->parent = $location_site;
			$location_membersonly->inherits = false;
			$location_membersonly->save();

			$location_adminonly = new Location();
			$location_adminonly->name = 'admin_only';
			$location_adminonly->resource = 'directory';
			$location_adminonly->parent = $location_site;
			$location_adminonly->inherits = false;
			$location_adminonly->save();



			// CREATE PERMISSION PROFILES

			// Read, Add, Edit, Delete, Execute
			PermissionActionList::addAction('Read');
			PermissionActionList::addAction('Add');
			PermissionActionList::addAction('Edit');
			PermissionActionList::addAction('Delete');
			PermissionActionList::addAction('Execute');
			PermissionActionList::addAction('System');
			PermissionActionList::addAction('Admin');

			// Add Admin permissions
			$adminRootPermissions = new GroupPermission($memgroup_admin->getId(), $location_root->getId());
			$adminRootPermissions->setPermission('universal', 'Read', true);
			$adminRootPermissions->setPermission('universal', 'Edit', true);
			$adminRootPermissions->setPermission('universal', 'Add', true);
			$adminRootPermissions->setPermission('universal', 'Delete', true);
			$adminRootPermissions->setPermission('universal', 'Execute', true);
			$adminRootPermissions->setPermission('universal', 'System', true);
			$adminRootPermissions->setPermission('universal', 'Admin', true);

			$adminOnlyPermissions = new GroupPermission($memgroup_admin->getId(), $location_adminonly->getId());
			$adminOnlyPermissions->setPermission('universal', 'Read', true);
			$adminOnlyPermissions->setPermission('universal', 'Edit', true);
			$adminOnlyPermissions->setPermission('universal', 'Add', true);
			$adminOnlyPermissions->setPermission('universal', 'Delete', true);
			$adminOnlyPermissions->setPermission('universal', 'Execute', true);
			$adminOnlyPermissions->setPermission('universal', 'System', true);
			$adminOnlyPermissions->setPermission('universal', 'Admin', true);

			$adminMembersPermissions = new GroupPermission($memgroup_admin->getId(), $location_membersonly->getId());
			$adminMembersPermissions->setPermission('universal', 'Read', true);
			$adminMembersPermissions->setPermission('universal', 'Edit', true);
			$adminMembersPermissions->setPermission('universal', 'Add', true);
			$adminMembersPermissions->setPermission('universal', 'Delete', true);
			$adminMembersPermissions->setPermission('universal', 'Execute', true);
			$adminMembersPermissions->setPermission('universal', 'System', true);
			$adminMembersPermissions->setPermission('universal', 'Admin', true);

			// Add private permissions
			$userMembersPermissions = new GroupPermission($memgroup_user->getId(), $location_membersonly->getId());
			$userMembersPermissions->setPermission('universal', 'Read', true);

			// Add public permissions
			$userSitePermissions = new GroupPermission($memgroup_user->getId(), $location_site->getId());
			$userSitePermissions->setPermission('universal', 'Read', true);

			$guestSitePermissions = new GroupPermission($memgroup_guest->getId(), $location_site->getId());
			$guestSitePermissions->setPermission('universal', 'Read', true);

		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function setupCoreModule()
	{
		try{

			$config = Config::getInstance();
			if(!class_exists('ModuleInstaller', false))
				include($config['path']['mainclasses'] . 'ModuleInstaller.class.php');

			$rootLocation = new Location(1);

			$defaultModules = array ('default' => 'BentoBase', 'error' => 'BentoBotch');

			foreach($defaultModules as $name => $package)
			{
				$postName = 'moduleInstall' . $name;


				$installation = new ModuleInstaller($package, $_POST[$postName], $location_site);
				if(!$installation->fullInstall())
				{
					throw new Exception('Module Installation failed.');
				}
				$rootLocation->meta[$name] = $package;
			}
			$rootLocation->save();

		}catch(Exception $e){
			return false;
		}
		return true;
	}
}

?>