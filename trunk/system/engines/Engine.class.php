<?php


abstract class Engine
{
	public $main_action;
	public $engine_type = 'engine';
	protected $content;
	protected $requiredPermission = false;
	protected $pathToModule;
	protected $className;
	protected $runMethod;
	
	protected $moduleId;
	protected $action;
	protected $moduleInfo;
	protected $package
	;
	public function __construct($moduleId, $action)
	{	
		$this->moduleId = $moduleId;
		$this->action = $action;
		$this->moduleInfo = new ModuleInfo($this->moduleId);
		$this->package = $this->moduleInfo['Package'];
		
		session_start();
		// I know this seems silly, but I want to give people a way to have a constructor while still reserving the real constructor for future needs
		$this->startEngine(); 
	}
	
	protected function startEngine()
	{
		
	}
	
	public function runModule()
	{
		try{
			$this->loadClass();
			$this->runAction();
		}catch (Exception $e){
			throw $e;
		}
	}
	
	protected function getRunMethod()
	{
		
		if(isset($this->runMethod))
		{
			return $this->runMethod;
		}
		
		return 'view' . $this->engine_type;
	}
	
	protected function loadClass()
	{
		try {

			$config = Config::getInstance();
			if(!$this->pathToModule)
			{
				$moduleInfo = new ModuleInfo($config['moduleId']);
				$modulePath = $this->moduleInfo['PathToPackage'];
			}else{
				$modulePath = $this->pathToModule;
			}
			
			if(!$this->className)
			{
				$this->className = ($this->package . 'Action' . $this->action);

			}
						
			
			$path = $modulePath . 'actions/' . $this->action . '.class.php';
			
			
			if(!class_exists($this->className, false))
			{
				if(!file_exists($path))
					throw new ResourceNotFoundError('Unable to load action file: ' . $path);
				include($path);	
			}

			
			$result = get_class_methods($this->className);
			
			if(!in_array($this->getRunMethod(), get_class_methods($this->className)))
				throw new ResourceNotFoundError('Action cannot run with this engine.');			

			
		}catch (Exception $e){
			throw new ResourceNotFoundError();
		}			
		return true;
	}
	
	protected function runAction()
	{
		try{
			$config = Config::getInstance();
			
			$runMethod = $this->getRunMethod();
			$reflectionClass = new ReflectionClass($this->className);
			
			
			if($reflectionClass->isSubclassOf('PackageAction'))
			{
				$this->main_action = new $this->className($this->package);
			}elseif($reflectionClass->isSubclassOf('Action')){
				$this->main_action = new $this->className($this->moduleId);
			}

			/*
			echo $this->requiredPermission;
			if($this->requiredPermission && !$this->main_action->isAllowed($this->requiredPermission))
				throw new AuthenticationError('Not allowed to access this engine at this location.');				
			*/
					
			if(!$this->main_action->checkAuth())
				throw new AuthenticationError('Not allowed to access this engine at this location.');				
			
			$this->processAction($this->main_action->$runMethod());		
			
		}catch (Exception $e){
			throw $e;
		}		
	}
	
	
	
	protected function processAction($actionResults)
	{
		$this->content = $actionResults;
	}

	public function display()
	{
		return $this->content;
	}
	
	public function finish()
	{
		$this->commit_logs();	
		//no more database access after this point!
		$dbConnector = DB_Connection::getInstance();
		unset($dbConnector);
		session_commit();
	}
	
	protected function commit_logs()
	{
		return true;
	}
	
	
}


?>