<?php

abstract class ModuleInstaller
{
	public $package; // outside set
	public $parent_location_id; // needs to be set, otherwise defaults to null
	public $settings = array(); // outside set
	public $name;
	
	public $previous_installs = false;
	public $module_id;
	public $location;
	public $info_class;
	
	public $error;
	
	public $profile = array();
	
	protected $updates = array(); // $update[#] = db_filename
	

	
	/*
	
	Example constructor 
	
	public function __construct()
	{
		// set update stuff
		$this->package = 'Name';
		
		//set permission stuff
		$this->profile['read_only'] = array('read');
		$this->profile['page_editor'] = array('add_page, edit_page, remove_page');
	}
	
	*/
	
	public function install()
	{
		$db = db_connect('default');

		$this->load_package_classes();
		
		try {
			$db->autocommit(false);
			
			$this->add_module_and_location();
			$this->package_check();
			
			$this->add_settings();

			$this->custom();

			$db->commit();

		}catch (Exception $e){
			$db = db_connect('default');
			$db->rollback();
			$this->error = $e->getMessage();
		}
		
		$db->autocommit(true);
	}
	
	protected function custom()
	{
		return true;
	}
	
	protected function load_package_classes()
	{
		$config = Config::getInstance();
		
		$path_to_package = $config['path']['packages'] . $this->package . '/';
		
		if(!class_exists($this->package . 'Info', false))
		{
			if(!include($path_to_package . 'info.php'))
				throw new Exception('Info Class Not Found');
		}
			
		$info_class_name = $this->package . 'Info';
		$this->info_class = new $info_class_name();	
						
	}
	
	protected function add_module_and_location()
	{
		$db = db_connect('default');
		$this->parent_location_id;
		$new_location = new ManageLocation();
		$new_location->name = $this->name;
		$new_location->resource_type = 'module';
		
		if($this->parent_location_id > 0)
		{
			$parent_location = new ManageLocation($this->parent_location_id);
			$new_location->parent = $parent_location;
		}
		
		$new_location->save();

		$this->location = $new_location;
		
		$module_stmt = $db->stmt_init();
		$module_stmt->prepare("INSERT INTO modules (mod_id, location_id, mod_name, mod_package) 
			VALUES (null, ?, ?, ?)");
		
		$module_stmt->bind_param_and_execute('iss', $this->location->id, $this->name, $this->package);
		$this->module_id = $module_stmt->insert_id;
		$module_stmt->close;
			
	}
	
	protected function add_settings()
	{
		if(count($this->settings) < 1)
			return;
		
		$db = db_connect('default');
		foreach($this->settings as $name => $value)
		{
			if(isset($value))
			{
				$stmt = $db->stmt_init();
				$stmt->prepare("INSERT INTO mod_config (config_id, mod_id, name, value) 
					VALUES (NULL, ?, ?, ?)");
			
				$stmt->bind_param_and_execute('iss', $this->module_id, $name, $value);
				$stmt->close;
			}
		}
	}
	

	protected function package_check()
	{
		$db = db_connect('default');
		$db_read_only = db_connect('default_read_only');
		$package_check = $db_read_only->stmt_init();
		$package_check->prepare("SELECT package_version FROM package_manager WHERE package_name = ?");
		$package_check->bind_param_and_execute('s', $this->package);
		
		if($package_check->num_rows > 0)
		{
			$this->previous_installs = true;
			
			$installed_package = $package_check->fetch_array();
			
			if($installed_package['package_version'] != $this->info['Version'])
			{
				$this->update_database($installed_package['package_version']);
				
				$package_install = $db->stmt_init();
				$package_install->prepare("UPDATE package_manager SET package_version = ?, package_lastupdated = NOW() WHERE package_name = ?");
				$package_install->bind_param_and_execute('ss', $this->info['Version'], $this->package);
				
			}
			
		}else{

			$package_install = $db->stmt_init();
			$package_install->prepare("INSERT INTO package_manager (package_name, package_version, package_lastupdated) VALUES (?, ?, NOW())");
			$package_install->bind_param_and_execute('ss', $this->package, $this->info_class['Version']);
			
				
			$this->load_sql('install');
			$this->add_permissions();
		}

	}
	
	protected function add_permissions()
	{
		$this->add_actions();			
		$profile_objects = array();
		
		$memgroup_admin = new MemberGroupManager();
		$memgroup_admin->name = 'Admin';
		$memgroup_admin->load();
		
		$location_root = new ManageLocation();
		$location_root->name = 'root';
		$location_root->load();
		
		foreach($this->profile as $profile_name => $profile_actions)
		{
			$tmp_profile = new PermissionProfile();
			$tmp_profile->name = $profile_name;
			$tmp_profile->load();

			foreach($profile_actions as $action)
			{
				$tmp_profile->add_action($action);
			}
				
			$tmp_profile->save();
			
			$memgroup_admin->add_permission($tmp_profile->id, $location_root->id);
			
			$profile_objects[$profile_name] = $tmp_profile;
		}
	}
	
	protected function add_actions()
	{
		$db = db_connect('default');
		foreach($this->info_class->actions as $action)
		{
			$stmt = $db->stmt_init();
			$stmt->prepare("INSERT IGNORE INTO actions (action_name) VALUES (?)");
			$stmt->bind_param_and_execute('s', $action->auth_action);
			$stmt->close;
		}
	}
	
	protected function add_plugins()
	{
		
	}

	protected function update_database($current_version)
	{
		return true;
	}
	
	protected function load_sql($sql_name)
	{
		$db = db_connect('default');
		$config = Config::getInstance();
		$path_to_package = $config['path']['packages'] . $this->package . '/';
		$sql = file_get_contents($path_to_package . 'sql/' . $sql_name . '.sql.php');
			
		if($sql && strlen($sql) > 0)
		{
			$db->multi_query($sql);
			
			do {
				if ($result = $db->store_result()) 
					$result->free_result();
				$db->more_results();
			} while ($db->next_result());
				
		}
		
	}
	
}


?>