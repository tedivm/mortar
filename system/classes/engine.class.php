<?php


abstract class Engine
{
	public $main_action;
	public $engine_type = 'engine';
	//protected $widgets;
	
	public function __construct()
	{	
		$config = Config::getInstance();
			
		if($config['module'] == 'default' && isset($this->default_module))
		{
			$config['module'] = $this->default_module;
		}	
		
		if($config['action'] == 'default' && isset($this->default_action))
		{
			$config['action'] = $this->default_action;
		}
		
		$config['engine'] = $this->engine_type;
	}
	
	public function run_module()
	{

		try {
			
			$this->load_class();
			
		}catch (Exception $e){
			throw new ResourceNotFoundError();
		}	
		
		try{
			$user = ActiveUser::get_instance();
			
			if(!$this->main_action->check_auth())
				throw new AuthenticationError();
							
			$this->main_action->run();
			
		}catch (Exception $e){
			throw $e;
		}
		
		
	}
	
	
	public function finish()
	{
		$this->commit_logs();	
	}
	
	public function start_widgets()
	{
		//$this->widgets = new EngineWidgetController($this->engine_type);
		//$this->widgets->start();
	}
	
	public function stop_widgets()
	{
		//$this->widgets->stop();
	}
	
	
	
	abstract public function display(); // this should output the very last thing
	

	protected function load_class()
	{
		$config = Config::getInstance();
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT mod_id, mod_package FROM modules WHERE mod_name=? LIMIT 1");
		$stmt->bind_param_and_execute('s', $config['module']);

		try{
		
			if($stmt->num_rows != 1)
			{
				throw new ExtendedError('No module in database');
			}
			
				
			$module = $stmt->fetch_array();
			
			$path_to_package = $config['path_packages'] . $module['mod_package'] . '/';
			
			$path_to_class = $path_to_package . 'info.php';
			
			if(!include($path_to_class))
				throw new ExtendedError('Info Class Not Found');
				
			$info_class = $module['mod_package'] . 'Info';
			$info = new $info_class($module['mod_id']);			

			$config['mod_class']  = $info->actions[$this->engine_type][$config['action']]->class;
			

			
			
			$path_to_module = $path_to_package . $config['mod_class'] . '.mod_' . $this->engine_type . '.php';
			if(!include($path_to_module))
				throw new ExtendedError('File not found ' . $path_to_module);
			
			
			$module_class = ucfirst($module['mod_package'])  . $config['mod_class'] . ucfirst($this->engine_type);
			
			$this->main_action = new $module_class($info);
							

				
				
		}catch(ExtendedError $e){
			throw $e;
		}
		
	}
	
	protected function commit_logs()
	{
		return true;
	}
	
	
}


?>