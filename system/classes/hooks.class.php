<?php

class Hook
{
	public $plugins = array();

	protected $location_id;
	protected $hook_name;
	protected $hook_type = 'Location';
	protected $hook_option;
	protected $option_table = 'plugin_lookup_location';
	protected $option_column = 'location_id';

	public function __construct($hook_option, $hook_name)
	{
		$this->hook_option = $hook_option;
		$this->hook_name = $hook_name;
		$this->selectPlugins();

		$location = new Location($LocationId);

		if(class_exists($location->resource . 'Hook'))
		{
			$ResourceSpecificHooks = new ModuleHook($location->id, $hook_name);
			$this->plugins = $this->plugins + $ResourceSpecificHooks->plugins;
		}
	}

	protected function selectPlugins()
	{

		$config = Config::getInstance();
		$cache = new Cache('hooks', $this->hook_type, $this->hook_option, $this->hook_name, 'plugin_data');
		$plugin_data = $cache->get_data();

		if(!$cache->cacheReturned)
		{
			$plugin_data = array();

			$plugins = new ObjectRelationshipMapper($this->option_table);
			$optionColumn = $this->option_column;
			$plugins->$optionColumn = $this->hook_option;
			$plugins->hook = $this->hook_name;


			if($plugins->select())
			{
				do
				{



					if(is_numeric($plugins->mod_id))
					{
						$pluginTemp['id'] = $plugins->mod_id;
						$moduleInfo = new ModuleInfo($plugins->mod_id, 'moduleId');
						$pluginTemp['package'] = $moduleInfo['Package'];
						$pluginTemp['type'] = 'module';
					}elseif($plugins->package_name){
						$pluginTemp['package'] = $plugins->package_name;
						$pluginTemp['type'] = 'package';
					}




					$pluginTemp['name'] = $plugins->name;


					// group === module
					$pluginTemp['classname'] = $pluginTemp['package'] . $pluginTemp['name'] . $this->hook_type . $this->hook_name;
					$pluginTemp['path'] = $config['paths']['module'] . $pluginTemp['package'] . '/hooks/' . $pluginTemp['name'] . '.' . $this->hook_type . '-' . $this->hook_name . '.php';







					$plugin_data[] = $pluginTemp;

				}while($plugins->next());
			}
			$cache->store_data($plugin_data);
		}

		if(count($plugin_data) < 1)
			return false;

		foreach($plugin_data as $plugin)
		{
			try
			{
				if(!class_exists($plugin['classname'], false))
				{
					if(file_exists($plugin['path']))
					{
						include($plugin['path']);
					}else{
						throw new BentoError('Unable to include plugin ' . $plugin['classname'] . ' at path ' . $plugin['path']);
					}
				}

				if(class_exists($plugin['classname'], false))
				{

					if($plugin['type'] == 'module')
					{
						$tmp_class = new $plugin['classname']($plugin['id'], $this->hook_option);
						$this->plugins[] = $tmp_class;

					}elseif($plugin['type'] == 'package'){
						$tmp_class = new $plugin['classname']($this->hook_option);
						$this->plugins[] = $tmp_class;
					}

				}else{
					throw new BentoError('Unable to load plugin ' . $plugin['classname']);
				}

			}catch(Exception $e){

			}

		}
	}


}

class EngineHook extends Hook
{
	protected $engine;
	protected $hook_type = 'Engine';
	protected $option_table = 'plugin_lookup_engine';
	protected $option_column = 'plugin_engine';

	public function __construct($engine, $hookName)
	{
		$this->hook_name = $hookName;
		$this->hook_option = $engine;

		if($engine != 'Universal')
			$this->plugins = new EngineHook('Universal', $hookName);

		$this->selectPlugins();

	}
}

class ModuleHook extends Hook
{

	protected $hook_type = 'Module';
	protected $option_table = 'plugin_lookup_module';
	protected $option_column = 'module_package';

	public function __construct($hook_option, $hookName)
	{
		$this->hook_name = $hookName;

		if(is_int($location))
			$location = new Location($location);

		$module = new ObjectRelationshipMapper('modules');
		$module->location_id = $location->id;
		$module->select(1);

		$this->hook_option = $module->mod_package;

		$this->selectPlugins();
	}

}







?>