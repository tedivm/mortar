<?php

class PluginInstaller
{
	public $group = '';
	public $id;
	public $hooks = array();
	public $settings = array();
	public $moduleParentID = '';
	
	public function __construct()
	{
		$this->group = '';
		// set package
		// load configuration
		
	}
	
	
	public function install()
	{
		$this->add_plugin();
		$this->load_sql('install');
		$this->add_hooks();
		
		if(method_exists($this, 'custom'))
			$this->custom();
	}
	
	protected function add_plugin()
	{
		$pluginRow = new ObjectRelationshipMapper('plugins');
		$pluginRow->plugin_group = $this->group;
		
		if(is_numeric($this->moduleParentID)) 
			$pluginRow->mod_id = $this->moduleParentID;
			
		$pluginRow->save();
		
		$this->id = $pluginRow->plugin_id;
	}
	
	protected function add_hooks()
	{
		// add engine hooks
		
		foreach($this->hooks as $hook)
		{
		
			switch ($hook['type']) {
					case 'module':
						$hookRow = new ObjectRelationshipMapper('plugin_lookup_module');
						$hookRow->module_package = $hook['target'];
						break;
						
					case 'engine':
						$hookRow = new ObjectRelationshipMapper('plugin_lookup_engine');
						$hookRow->plugin_engine = $hook['target'];
						break;
						
					case 'parentModule':
						if(!is_numeric($this->moduleParentID))
							continue 2;	// skip for now, throw an error of some sort later
						$hookRow = new ObjectRelationshipMapper('plugin_lookup_location');
						$hookRow->location_id = $this->moduleParentID;
						break;
				}	
				
				$hookRow->hook = $hook['hook'];
				$hookRow->name = $hook['name'];
				$hookRow->save();
		}
		
		// add modules-specific universal hooks
		
		// add plugin 
	}
	
	
	protected function add_hook($name, $hook, $type, $target = '')
	{
		$this->hooks[] = array('name' => $name,
				'hook' => $hook,
				'type' => $type,
				'target' => $target);
	}
	
	protected function add_settings()
	{
		if(!(count($this->settings) > 0 && $this->id > 0))
			return;
			
		foreach($this->settings as $name => $value)
		{
			$settingRow = new ObjectRelationshipMapper('plugin_config');
			$settingRow->plugin_id = $this->id;
			$settingRow->name = $name;
			$settingRow->value = $value;
			$settingRow->save();
		}
	}
	
	protected function load_sql($sql_name)
	{
		$db = db_connect('default');
		$config = Config::getInstance();
		$pathToPackage = $config['path']['packages'] . $this->package . '/';
		$sql = file_get_contents($pathToPackage . 'sql/' . $sql_name . '.sql.php');
			
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