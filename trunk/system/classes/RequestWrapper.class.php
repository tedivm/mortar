<?php

class RequestWrapper
{
	public static $ioHandlerType = 'Http';
	protected $ioHandler;
	protected $internalIoHandlers = array('Http', 'Rest', 'Cli', 'Install');

	protected $currentLocation;

	public function main()
	{

		try{
			$query = Query::getQuery();
			$handlerClass = $this->loadIoHandler();
			$this->ioHandler = new $handlerClass();
			do{
				try{
					$action = $this->getAction();
					$this->runAction($action);


				}catch(Exception $e){


					echo 'Attempting to deal with above errors';


					$errorAction = $this->handleError($e);
					$this->runAction($errorAction);
				}

			// If the io handler says it can handle another request, loop around and go for it
			}while($this->ioHandler->nextRequest());

		}catch(Exception $e){
			// If we're here we bailed out of the program loop due to an error with the error handler
			echo 'There was an error, and when attempting to deal with that error there was another error.';
		}


		try{

		}catch(Exception $e){
			$this->ioHandler->close();
			$this->close();
		}

	}

	public function getHandler()
	{
		return $this->ioHandler;
	}

	protected function runAction($action)
	{
		$outputController = $this->loadFormatHandler();
		$outputController->initialize($action);
		$output = $outputController->getFinalOutput();
		$this->ioHandler->output($output);
	}

	protected function getAction($className = null, $argument = null)
	{
		// if we aren't given the classname and argument, we load them up
		if(!$className)
		{
			$actionClassInfo = $this->loadActionClass();
			$className = $actionClassInfo['className'];
			$argument = $actionClassInfo['argument'];
		}

		// Check the class to make sure its usable with the current settings
		$reflectionClass = new ReflectionClass($className);

		// match interface
		if(!in_array('ActionInterface', $reflectionClass->getInterfaceNames()))
			throw new BentoError('Class should implement interface');

		// Create the class
		$action = new $className($argument, $this->ioHandler);

		// Check authentication
		if(!$action->checkAuth())
			throw new AuthenticationError('Not allowed to access this action at this location.');

		return $action;

	}

	protected function loadFormatHandler()
	{
		$config = Config::getInstance();
		$query = Query::getQuery();
		$format = preg_replace("/[^a-zA-Z0-9s]/", '', $query['format']);

		if(!class_exists('AbstractOutputController', false))
		{
			$path = $config['path']['mainclasses'] . 'RequestWrapper/OutputControllers/Abstract.class.php';

			if(!include($path))
				throw new BentoError('Unable to load file ' . $path);

			if(!class_exists('AbstractOutputController'))
				throw new BentoError('Unable to find output filter AbstractOutputController');
		}

		$formatFilter = $format . 'OutputController';

		if(!class_exists($formatFilter, false))
		{
			$config = Config::getInstance();

			$filename = $format . '.class.php';
			$path = $config['path']['mainclasses'] . 'RequestWrapper/OutputControllers/' . $format . '.class.php';

			if(!include($path))
				throw new BentoError('Unable to load file ' . $path);

			if(!class_exists($formatFilter, false))
				throw new BentoError('Unable to find output filter ' . $formatFilter);
		}

		$controller = new $formatFilter($this->getHandler());

		return $controller;
	}

	protected function handleError($e)
	{
		$site = ActiveSite::getSite();
		$errorModule = $site->location->meta('error');

		switch (get_class($e))
		{
			case 'AuthenticationError':
				$action = 'LogIn';
				$errorModule = $site->location->meta('default');
				break;

			case 'ResourceNotFoundError':
				$action = 'ResourceNotFound';
				break;

			case 'BentoWarning':
			case 'BentoNotice':
				// uncaught minor thing

			case 'BentoError':
			default:
				$action = 'TechnicalError';
				break;
		}

		$moduleInfo = new PackageInfo($errorModule);
		$actionInfo = $moduleInfo->getActions($action);

		if(!class_exists($actionInfo['className']))
			include($actionInfo['path']);

		return $this->getAction($actionInfo['className']);
	}

	protected function loadIoHandler($handlerName = null)
	{
		if(!$handlerName)
			$handlerName = self::$ioHandlerType; staticHack(get_class($this), 'ioHandlerType');

		// I know this is lame, but its the easiest way to deal with this dependency until we get namespaces
		if($handlerName != 'Cli')
			$this->loadIoHandler('Cli');

		if(in_array($handlerName, $this->internalIoHandlers))
		{
			$className = 'IOProcessor' . $handlerName;

			if(!class_exists($className, false))
			{
				$config = Config::getInstance();
				$path = $config['path']['mainclasses'] . 'RequestWrapper/IOProcessors/' . $handlerName . '.class.php';

				if(file_exists($path))
					include($path);
			}

		}else{
			$className = $handlerName;
		}

		if(!class_exists($className, false))
			throw new BentoError('Unable to find request handler: ' . $className);

		return $className;
	}

	protected function close()
	{
		$this->requestHandler->close();

		//no more database access after this point!
		DatabaseConnection::close();
	}

	protected function loadActionClass()
	{
		$query = Query::getQuery();

		if(isset($query['location']))
			$locationId = $query['location'];

		try {

			if($query['module'])
			{
				$moduleInfo = new PackageInfo($query['module']);

				if($moduleInfo->getStatus() != 'Installed')
					throw new ResourceNotFoundError('Module not installed');

				$action = ($query['action']) ? $query['action'] : 'Default';

				if(!($actionInfo = $moduleInfo->getActions(($query['action']) ? $query['action'] : 'Default')))
					throw new ResourceNotFoundError();


				$className = $actionInfo['className'];
				$path = $actionInfo['path'];
				$argument = '';

				if(!class_exists($className, false))
				{
					if(!include($actionInfo['path']) || !class_exists($className, false))
						throw new ResourceNotFoundError('Unable to load action class ' . $className
															. ' at location: ' . $actionInfo['path']);
				}

				return array('className' => $className, 'argument' => $argument);

			}
		}catch(Exception $e){
			throw new ResourceNotFoundError();
		}
			// get location

		if(is_numeric($query['location']))
			$location = new Location($query['location']);


		if(!isset($location))
		{
			$site = ActiveSite::getSite();
			$location = $site->getLocation();
		}

		$model = $location->getResource();
		// figure out what to do with it
		$locationResourceInfo = $location->getResource(true);
		$modelHandler = ModelRegistry::getHandler($locationResourceInfo['type']);

		if(!$modelHandler)
			throw new ResourceNotFoundError('Unable to load model handler.');

		if($query['action'] != 'Add')
		{
			$model = $location->getResource();

			if(!$model->checkAuth($query['action']))
				throw new AuthenticationError();

			$actionInfo = $model->getAction($query['action'] ? $query['action'] : 'Read');
			$className = $actionInfo['className'];
			$path = $actionInfo['path'];

			$argument = $model;
		}else{

		}

		if(!class_exists($className, false))
		{
			if(!(file_exists($path) && include($path)) || !class_exists($className, false))
				throw new ResourceNotFoundError('Unable to load action class ' . $className . ' at location: ' . $path);
		}

		return array('className' => $className, 'argument' => $argument);
	}

}



?>