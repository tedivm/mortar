<?php

abstract class ModuleBase
{
	protected $moduleId;
	protected $moduleName;

	protected $location;
	protected $settings = array();

	protected $pathToPackage;
	protected $package;

	public $packageInfo;

	protected function loadSettings()
	{
		if(isset($this->moduleId))
		{
			$package = $info['Package'];
		}elseif(isset($this->package)){
			$package = $this->package;
		}

		if(isset($package))
		{
			$packageInfo = new PackageInfo($package);
			$this->packageInfo = $packageInfo;
			$this->package = $packageInfo->getName();
			$this->pathToPackage = $packageInfo->getPath();
			$this->moduleId = $packageInfo->getId();
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