<?php

class BentoBaseActionInstall //extends Action 
{

	protected $form = true;
	protected $error;
	protected $installed = false;
	
	public $subtitle = '';
	
	public function __construct()
	{
		
		$config = Config::getInstance();
		
		try{
			
			
			// Step 0 - Check for php requirements
			
			//0a Check for .blockinstall
		
			try{	
			
					
				if(file_exists($config['path']['base'] . '.blockinstall'))
					throw new Exception('blockinstall file found.');			
					
				//0a Check for 
				if(file_exists($config['path']['base'] . 'data/configuration/main_config.php'))
					throw new Exception('Configuration file already exists');					
							
				if(file_exists($config['path']['base'] . '.install'))
					$lastCompletedStep = file_get_contents($config['path']['base'] . '.install');
	
			}catch(Exception $e){
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
				
	
				try{
						
					
								
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
						
						$configFile->set('url', 'theme', $url['theme']);
						$configFile->set('url', 'modules', $url['modules']);
						$configFile->set('url', 'javascript', $url['javascript']);
						
						$configFile->set('cache', 'handler', $cache);
						
						$configFile->write();
					
					}else{
							
					}
								
					$config->reset();
					
					
				}catch(Exception $e){
				
					// Error Writing Config File
					throw new Exception('Error Setting Configuration File', 1);
				}
				
							
				try{			
								
					// Check Database Connections
								
					$dbIniFile = new IniFile($directory . 'databases.php');
								
					if(!$_POST['DBhost'] || !$_POST['DBusername'] || !$_POST['DBpassword'] || !$_POST['DBname'])
						throw new Exception('No Database information', 1);

						
					if($connection = new mysqli($_POST['DBhost'], $_POST['DBusername'], $_POST['DBpassword'], $_POST['DBname']))
					{
						$dbIniFile->set('default', 'username', $_POST['DBusername']);
						$dbIniFile->set('default', 'password', $_POST['DBpassword']);
						$dbIniFile->set('default', 'host', $_POST['DBhost']);
						$dbIniFile->set('default', 'dbname', $_POST['DBname']);

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
					
					// Error Writing Database Config Files
					throw new Exception($message, 2);
				}				
		
	
						
						
				try{
					
		
					// Sanity check on previous data
					$config->reset();
							
					
							
					// Set Up database structure
							
					$pathToSQL = $config['path']['modules'] . 'BentoBase/sql/system_install.sql.php';
					$sql = file_get_contents($pathToSQL);
							

					
					if ($connection->multi_query($sql)) 
					{
						do {
							if ($result = $connection->store_result()) {
								$result->free();
							}
							if ($connection->more_results()) {
							}
						} while ($connection->next_result());
					}
					
					
					
					
					
				}catch(Exception $e){
				
					$message = 'Unable to load database structure.';
					// Error Inserting Database
					throw new Exception($message, 3);
				
				}	
	
				
				try{		
						
					// CREATE USERS
					if(!class_exists('ManageUser', false))
					{
						include($config['path']['mainclasses'] . 'permissions_editing.class.php');
					}
					$user_admin = new ManageUser();
					$user_admin->user_name = $_POST['username'];
					$user_admin->user_password = $_POST['password'];
					$user_admin->save();
							
					$user_guest = new ManageUser();
					$user_guest->user_name = 'guest';
					$user_guest->save();
					
					
							
					// CREATE MEMBERGROUPS
					$memgroup_admin = new MemberGroupManager();
					$memgroup_admin->name = 'Admin';
					$memgroup_admin->save_membergroup();
							
					$memgroup_guest = new MemberGroupManager();
					$memgroup_guest->name = 'Guest';
					$memgroup_guest->save_membergroup();
							
					$memgroup_user = new MemberGroupManager();
					$memgroup_user->name = 'User';
					$memgroup_user->save_membergroup();
							
							
					// ADD USERS TO MEMBERGROUPS
					$memgroup_admin->add_user($user_admin->user_id);
					$memgroup_user->add_user($user_admin->user_id);
					$memgroup_guest->add_user($user_guest->user_id);
							
						
					// CREATE PERMISSION PROFILES
							
					$profile_readonly = new PermissionProfile();
					$profile_readonly->name = 'Read Only';
					$profile_readonly->description = '';
					$profile_readonly->add_action('Read');
					$profile_readonly->save();

					$profile_owner = new PermissionProfile();
					$profile_owner->name = 'Owner';
					$profile_owner->description = '';
					$profile_owner->add_action('Read');
					$profile_owner->add_action('Add');
					$profile_owner->add_action('Edit');
					$profile_owner->add_action('Delete');
					$profile_owner->add_action('Execute');
					$profile_owner->save();
					
					$profile_admin = new PermissionProfile();
					$profile_admin->name = 'Full Permissions';
					$profile_admin->description = '';
					$profile_admin->add_action('Admin');
					$profile_admin->add_action('Read');
					$profile_admin->add_action('Add');
					$profile_admin->add_action('Edit');
					$profile_admin->add_action('Delete');
					$profile_admin->add_action('Execute');
					$profile_admin->add_action('System');
					$profile_admin->save();
							
							
					// CREATE ROOT LOCATION
					$location_root = new Location();
					$location_root->name = 'root';
					$location_root->resource = 'directory';
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
					
					$siteMeta['adminTheme'] = 'admin';
					$siteMeta['htmlTheme'] = 'default';
					
					foreach($siteMeta as $name => $value)
					{
						$siteMetaRecord = new ObjectRelationshipMapper('sites');
						$siteMetaRecord->site_id = $site->site_id;
						$siteMetaRecord->name = $name;
						$siteMetaRecord->value = $value;
						$siteMetaRecord->save();						
					}
					
					
					
					
					
					
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
					//
		
		
					// ADD ROOT PERMISSIONS
					$memgroup_admin->add_permission($profile_admin->id, $location_root->location_id());
					$memgroup_admin->add_permission($profile_admin->id, $location_adminonly->location_id());
					$memgroup_admin->add_permission($profile_admin->id, $location_membersonly->location_id());
							
					// ADD PUBLIC PERMISSIONS
					$memgroup_user->add_permission($profile_readonly->id, $location_site->location_id());
					$memgroup_guest->add_permission($profile_readonly->id, $location_site->location_id());
							
					// ADD PRIVATE PERMISSIONS
					$memgroup_user->add_permission($profile_readonly->id, $location_membersonly->location_id());
					
				}catch(Exception $e){
					$message = 'Error setting up permissions';
					// Error Inserting Permissions
					throw new Exception($message, 4);
				}
						
				
				try{
					
					if(!class_exists('InstallModule', false))
					{
						include($config['path']['mainclasses'] . 'InstallModule.class.php');
					}
					
					$defaultModules = array ('default' => 'BentoBase', 'error' => 'BentoBotch');
					
					foreach($defaultModules as $name => $package)
					{
						$postName = 'moduleInstall' . $name;
						$installation = new InstallModule($package, $_POST[$postName], $location_site);
						
						if(!$installation->installModule())
						{
							throw new Exception('Module Installation failed.');
						}						
						
						$location_site->meta[$name] = $installation->id;
					}
					
					$location_site->save();
									
				}catch(Exception $e){
					$message = 'Error installing Core module.';
					// Error installing Core Module
					throw new Exception($message, 5);
				}
				
				
				
			}
			
			
			file_put_contents($config['path_base'] . '.blockinstall', 'To unblock installation, delete this file.');
			$this->installed = true;			
			
			
		}catch (Exception $e){
			
			
			
			$this->installed = false;
			$this->error = $e->getMessage();
			// step back through the program undoing everything up to the number
			switch ($e->getCode()) {
				case 5:
					//module
				case 4:	
					//permissions
				case 3:	
					// database
					
					
					
					
				case 2:
					// database files
					unlink($config['path']['base'] . 'data/configuration/databases.php');
				case 1:
					// configuration files
					unlink($config['path']['base'] . 'data/configuration/configuration.php');
				default:
					break;
			}
			
			$this->error = $e->getMessage();
			
		}
		
	}
	
	public function viewInstall()
	{
		$config = Config::getInstance();
		$output = '';
		
		$modulePath = $config['path']['modules'] . 'BentoBase/';
		
		if($this->error)
		{
			$output .= '<div id="error" class="error">' . $this->error . '</div>';
		}
		
		
		if($this->installed)
		{
			
			$output .= file_get_contents($modulePath . 'templates/installationComplete.template.php');
			$this->subtitle = 'Installation Complete';
				
		}elseif($this->form){
				
			$form = new Form('installation');

			$form->disableXsfrProtection()->
			
			
				changeSection('system')->
					setLegend('System Information')->
					
					
					createInput('siteName')->
						setLabel('Site Name')->
						addRule('required')->
						addRule('letterswithbasicpunc')->
					getForm()->
					
					createInput('domain')->
						setLabel('Domain')->
						property('value', $_SERVER['SERVER_NAME'] . substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], DISPATCHER)))->
						addRule('required')->
					getForm()->
					
					createInput('ssl')->
						setLabel('SSL Domain')->
					getForm()->
	
					createInput('moduleInstalldefault')->
						setLabel('System Module Name')->
						property('value', 'System')->
						addRule('required')->
					getForm()->

					createInput('moduleInstallerror')->
						setLabel('Error Module Name')->
						property('value', 'Error')->
						addRule('required')->
					getForm()->

									
					createInput('base')->
						setLabel('Base Path')->
						property('value', $config['path']['base'])->
						addRule('required')->
					getForm()->	
					
					
								
				changeSection('admin')->
					setLegend('Administrative User')->
					
					createInput('username')->
						setLabel('Username')->
						property('value', 'administrator')->
						addRule('required')->
					
					getForm()->
					
					createInput('password')->
						setType('password')->
						setLabel('Password')->
						addRule('required')->					
						addRule('minlength', '8');
					

					
				$cacheInput = $form->changeSection('cache')->
					setLegend('Cache Settings')->
					
					createInput('cacheHandler')->
						setLabel('Cache Handler')->
						setType('select')->
						addRule('required');
					
						
					$cacheHandlers = Cache::getHandlers();	
						
					foreach($cacheHandlers as $cacheName => $cacheClass)
					{
						$cacheInput->setOptions($cacheName, $cacheName);
					}

					
					
				$form->changeSection('mainDatabase')->
					setLegend('Main Database Connection')->
					setSectionIntro('This is the primary database connection. This user needs to have full access to the database.')->
					
									
					createInput('DBname')->
						setLabel('Database')->
						addRule('required')->
					
					getForm()->
					
					createInput('DBusername')->
						setLabel('User')->
						addRule('required')->
					getForm()->
					
					createInput('DBpassword')->
						setLabel('Password')->
						addRule('required')->
					
					getForm()->
					
					createInput('DBhost')->
						setLabel('Host')->
						property('value', 'localhost')->
						addRule('required')->
					
					getForm()->
					
					
				changeSection('readonlyDatabase')->
					setLegend('Read Only Database Connection')->
					setSectionIntro('This is the read only database connection, which all of the select statements use. If you do not have a seperate user for this you may leave it blank.')->
						
					createInput('DBROname')->
						setLabel('Database')->
					getForm()->
					
					createInput('DBROusername')->
						setLabel('User')->
					getForm()->
					
					createInput('DBROpassword')->
						setLabel('Password')->
					getForm()->
					
					createInput('DBROhost')->
						setLabel('Host')->
						property('value', 'localhost');				
			
			
			
			$output .= $form->makeDisplay();
			
		}
		
		
		return $output;
	}

}

?>