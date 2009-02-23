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


					exit();

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

	protected function runAction($action)
	{
		$outputFilter = $this->loadFormatHandler();
		$outputFilter->initialize($action);
		$output = $outputFilter->getFinalOutput();
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
		$action = new $className($argument);

		// Check authentication
		if(!$action->checkAuth())
			throw new AuthenticationError('Not allowed to access this engine at this location.');

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

			if(!class_exists($formatFilter))
				throw new BentoError('Unable to find output filter ' . $formatFilter);
		}

		$controller = new $formatFilter();

		return $controller;
	}

	protected function handleError($e)
	{
		$site = ActiveSite::getInstance();
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

		if(isset($query['locationId']))
			$locationId = $query['locationId'];

		$pathArray = $query['pathArray'];



		if($query['module'] || $pathArray[0] == 'module')
		{
			if($pathArray[0] == 'module')
			{
				array_shift($pathArray);
				$module = array_shift($pathArray);
			}else{
				$module = $query['module'];
			}

			$moduleInfo = new PackageInfo($module);

			if(is_array($pathArray) && count($pathArray) > 0)
			{
				if($url->loadTemplate($modelHandler['resource'] . 'UrlMapping', $moduleInfo->getName())
					|| $url->loadTemplate('UrlMapping', $moduleInfo->getName())
					|| $url->setDisplayTemplate('{# action #}/{# id #}/'))
					{
						$this->customQueryFromPath($url->tagsUsed(), $pathArray);
						$query = Query::getQuery();
					}
			}

			$action = ($query['action']) ? $query['action'] : 'Default';




		}else{


			// get location

			if(is_numeric($query['location'])){
				$location = new Location($query['location']);

			}elseif(count($pathArray) > 0){

				$pathResults = $this->processPathArray($pathArray);
				$pathArray = $pathResults['pathArray'];

				if(is_numeric($pathResults['locationId']))
					$location = new Location($pathResults['locationId']);

			}

			if(!isset($location))
			{
				$site = ActiveSite::getInstance();
				$location = $site->getLocation();
			}



			// figure out what to do with it

			$modelHandler = ModelRegistry::getHandler($location->getResource());
			$moduleInfo = new PackageInfo($modelHandler['module']);

			if(is_array($pathArray) && count($pathArray) > 0)
			{
				if($url->loadTemplate($modelHandler['resource'] . 'UrlMapping', $moduleInfo->getName())
					|| $url->loadTemplate('UrlMapping', $moduleInfo->getName())
					|| $url->setDisplayTemplate('{# action #}/{# id #}/'))
					{
						$this->customQueryFromPath($url->tagsUsed(), $pathArray);
						$query = Query::getQuery();
					}
			}

			$action = $query['action'] ? $query['action'] : 'read';
			$actionInfo = staticFunctionHack($modelHandler['class'], 'getAction', $action);
			$actionInfo['argument'] = $location;



	}




		if(!class_exists($actionInfo['className']))
			include($actionInfo['path']);












		return array('className' => $actionInfo['className'], 'argument' => $actionInfo['argument']);
	}

	protected function customQueryFromPath($tags, $pathArray)
	{
		if(count($tags) > 0)
		{
			$query = Query::getQuery();
			foreach($tags as $name)
			{
				$query[$name] = array_shift($pathArray);
			}
			$query->save();
		}
	}

	protected function processPathArray($pathArray)
	{
		$site = ActiveSite::getInstance();
		$currentLocation = $site->getLocation;
		$location = new Location();

		foreach($pathArray as $pathIndex => $pathPiece)
		{
			if(!($childLocation = $currentLocation->getChildByName(str_replace('_', ' ', $pathPiece))))
			{
				// will only execute during the last iteration
				while(ModelRegistry::getHandler($currentLocation->getResource()) === false)
				{
					if($childLocation = $currentLocation->getDefaultChild())
					{
						$currentLocation = $childLocation;
					}else{
						break;
					}
				}

				// bust out of forloop
				break;
			}else{
				// if the current location has a child with the next path pieces name, we descend
				$currentLocation = $childLocation;
				unset($pathArray[$pathIndex]);
			}
		}

		$info['locationId'] = $currentLocation->getId();
		$info['pathArray'] = $pathArray;

		return $info;
	}

}



?>