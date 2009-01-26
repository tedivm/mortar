<?php

abstract class Engine
{
	public $main_action;
	public $engine_type = 'engine';

	protected $requiredPermission = false;

	protected $action;

	protected $package;
	protected $location;
	protected $moduleId;

	public function __construct($package = NULL, $action = '')
	{
		$info = InfoRegistry::getInstance();
		$runtime = RuntimeConfig::getInstance();

		if(isset($package))
		{
			$this->package = $package;
			$this->action = $action;
		}else{
			$this->package = $info->Runtime['package'];
			$this->action = (strlen($action) > 0) ? $action : $info->Runtime['action'];
		}

		$packageInfo = new PackageInfo($this->package);

		if($packageInfo->getStatus() != 'installed')
			throw new ResourceNotFoundError();


		$this->className = $this->package . 'Action' . $this->action;
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
			$info = InfoRegistry::getInstance();

			$config = Config::getInstance();
			if(!$this->pathToModule)
			{
				$packageInfo = new PackageInfo($this->package);
				$modulePath = $packageInfo->getPath();
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
				throw new ResourceNotFoundError('Action cannot run with this engine because it doesn\'t contain the
							right run method.');


			AutoLoader::import($this->package);
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

			$interfaces = $reflectionClass->getInterfaceNames();

			if(!in_array('ActionInterface', $interfaces))
				throw new BentoError('Class should implement interface');

			if($reflectionClass->isSubclassOf('PackageAction'))
			{
				$actionIdentifier = $this->package;
			}elseif($reflectionClass->isSubclassOf('Action')){
				$actionIdentifier = $this->moduleId;
			}


			//var_dump($actionIdentifier);
			$this->main_action = new $this->className($actionIdentifier);

			if(!$this->main_action->checkAuth())
				throw new AuthenticationError('Not allowed to access this engine at this location.');

			$settingsArrayName = $this->engine_type . 'Settings';


			if($this->requiredPermission && !isset($this->main_action->{$settingsArrayName}['EnginePermissionOverride']))
			{
				if(!$this->main_action->checkAuth($this->requiredPermission))
					throw new AuthenticationError('Not allowed to access this engine at this location.');
			}

			$this->main_action->start();
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