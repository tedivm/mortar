<?php

abstract class ModuleBase
{
	protected $moduleId;
	protected $moduleName;
	protected $modulePackage;
	protected $location;
	protected $settings = array();	
	protected $pathToPackage;
	protected $package;
	
	public $packageInfo;
	
	protected function loadSettings()
	{	
		if(isset($this->moduleId))
		{
			$info = new ModuleInfo($this->moduleId);
			$this->location = new Location($info['locationId']);
			$this->moduleName = $info['Name'];
			$this->settings = $info->settings;
		}
		
		if(isset($this->package))
		{
			$this->packageInfo = new PackageInfo($this->package);
			$this->pathToPackage = $this->packageInfo->path;
		}
	}
	
	// temporary until we finish cleanup
	protected function load_template($name)
	{
		return $this->loadTemplate($name);
	}
	
	protected function loadTemplate($name)
	{
		$config = Config::getInstance();
		
		$template_path = $config['path']['theme'] . 'packages/' . $this->package . '/' . $name . '.template.php';
		
		if(file_exists($template_path))
			return file_get_contents($template_path);
		
		$template_path = $config['path']['modules'] . $this->package . '/templates/' . $name .'.template.php';

		if(file_exists($template_path))
			return file_get_contents($template_path);
				
	}	
	
	protected function loadHelper($class)
	{
		try {
			if(!($instance = load_helper($this->package, $class)))
				throw new BentoNotice('Unable to load helper class ' . $class . ' in package ' . $this->package);
			
		}catch (Exception $e){
			return false;
		}
		
		return $instance;
	}
	
	
}

?>