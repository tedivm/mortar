<?php

abstract class Action extends ModuleBase 
{
	static $requiredPermission = 'Read';
	protected $permissions;
	protected $engineHelper;
	
	public function __construct($modId)
	{
		$this->moduleId = $modId;
		$this->loadSettings();
		$this->startUp();
	}
	
	public function startUp()
	{
		$config = Config::getInstance();
		
		$pathToEngineHelper = $config['path']['engines'] . $config['engine'] . '.engineHelper.php';

		$className = $config['engine'] . 'Helper';
		
		if(!class_exists($className, false) && file_exists($pathToEngineHelper))
			include($pathToEngineHelper);
			
		if(class_exists($className, false))
			$this->engineHelper = new $className();

		if(method_exists($this, 'logic'))
			$this->logic();		
	}
	
	public function check_auth()
	{
		return $this->checkAuth();
	}
	
	public function checkAuth()
	{
		return $this->is_allowed(staticHack($this, 'requiredPermission')); // retarded hack until php 5.3 comes out
	}

    public function is_allowed($action)
    {
    	return $this->isAllowed($action);
    }
    
	
    public function isAllowed($action)
    {
        if(!$this->permissions)
        {
            $user = ActiveUser::get_instance();
            $this->permissions = new Permissions($this->location, $user->id);
        }
        
    	return $this->permissions->is_allowed($action);
    }
    
    
   
    
    
	/*
	
	Example displays
	
	public function viewAdmin();
	
	public function viewHtml();
	
	public function viewXml();
	
	public function viewRss();
	
	*/
	
	
}

abstract class PackageAction extends Action
{
	
	public function __construct($package)
	{
		
		$this->package = $package;
		$this->loadSettings();
		$this->startUp();
	}	
	
    public function isAllowed($action)
    {
        $user = ActiveUser::get_instance();
        $packageInfo = new PackageInfo($this->package);
        
        return $packageInfo->checkAuth($action);
    }	
}

?>