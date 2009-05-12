<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This class returns the arguments or query (get) values sent by the system
 *
 * @package MainClasses
 */
class RequestWrapper
{
	/**
	 * The current ioHandler type, generally Http, Rest or Cli
	 *
	 * @static
	 * @var string
	 */
	public static $ioHandlerType = 'Http';

	/**
	 * This is the ioHandler currentler running
	 *
	 * @access protected
	 * @var ioHandler
	 */
	protected $ioHandler;

	/**
	 * This is a list of internal ioHandlers
	 *
	 * @var array
	 */
	protected $internalIoHandlers = array('Http', 'Rest', 'Cli', 'Install');

	/**
	 * Current running location
	 *
	 * @var Location
	 */
	protected $currentLocation;

	/**
	 * This is where the magic happens, and the users request is carried out/
	 *
	 */
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

	/**
	 * Returns the current ioHandler
	 *
	 * @return ioHandler
	 */
	public function getHandler()
	{
		return $this->ioHandler;
	}

	/**
	 * Returns the class name and argument for the current request
	 *
	 * @access protected
	 * @return array
	 */
	protected function loadActionClass()
	{
		$query = Query::getQuery();

		if(isset($query['location']))
			$locationId = $query['location'];

		if(is_numeric($query['location']))
			$location = new Location($query['location']);

		try {

			if($query['module'])
			{
				$moduleInfo = new PackageInfo($query['module']);

				if($moduleInfo->getStatus() != 'installed')
					throw new BentoError('Module ' . $query['module'] . ' present but not installed');

				$action = ($query['action']) ? $query['action'] : 'Default';

				if(!($actionInfo = $moduleInfo->getActions(($query['action']) ? $query['action'] : 'Default')))
					throw new BentoError('Unable to load action for module ' . $query['module']);


				$argument = '';
				$className = importFromModule($actionInfo['name'], $query['module'], 'action', true);


				return array('className' => $className, 'argument' => $argument);
			}

		}catch(Exception $e){
			throw new ResourceNotFoundError();
		}

		if($query['action'] != 'Add')
		{
			if(!isset($location))
			{
				$site = ActiveSite::getSite();
				$location = $site->getLocation();
			}

			$model = $location->getResource();
			$actionInfo = $model->getAction($query['action'] ? $query['action'] : 'Read');
			$className = $actionInfo['className'];
			$path = $actionInfo['path'];
			$argument = $model;

		}else{

			if(isset($query['type']))
			{
				$type = $query['type'];
			}elseif(isset($location)){
				$parentModel = $location->getResource();
				if(!($type = $parentModel->getDefaultChildType()))
				{
					throw new ResourceNotFoundError('Attempted to add resource without specifying type', 400);
				}
			}else{
				throw new ResourceNotFoundError('Attempted to add resource without specifying type', 400);
			}

			$modelHandler = ModelRegistry::loadModel($type);
			if(!$modelHandler)
				throw new ResourceNotFoundError('Unable to load model handler.');

			$model = new $modelHandler();
			$actionInfo = $model->getAction('Add');
			$argument = $model;

			if(isset($location))
			{
				$model->setParent($location);
			}
		}

		if(isset($actionInfo))
		{
			$className = $actionInfo['className'];
			$path = $actionInfo['path'];
		}

		if(!class_exists($className, false))
		{
			if(!(file_exists($path) && include($path)) || !class_exists($className, false))
				throw new ResourceNotFoundError('Unable to load action class ' . $className . ' at location: ' . $path,
				 '405');
		}

		return array('className' => $className, 'argument' => $argument);
	}

	/**
	 * This function takes a className and argument and returns the action object
	 *
	 * @param string $className
	 * @param string $argument
	 * @return Action
	 */
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
			throw new BentoError($reflectionClass->getName() . ' should implement ActionInterface');

		// Create the class
		$action = new $className($argument, $this->ioHandler);
		return $action;
	}

	/**
	 * This function takes an action, runs it, and outputs the result through the current ioHandler
	 *
	 * @access protected
	 * @param Action $action
	 */
	protected function runAction($action)
	{
		$outputController = $this->loadFormatHandler();
		$outputController->initialize($action);
		$output = $outputController->getFinalOutput();
		$this->ioHandler->output($output);
	}

	/**
	 * Loads the output format handler
	 *
	 * @access protected
	 * @return OutputFormatter
	 */
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

	/**
	 * When an action throws an error this function loads the action to handle it
	 *
	 * @param exception $e
	 * @return Action
	 */
	protected function handleError($e)
	{
		$site = ActiveSite::getSite();
		$location = $site->getLocation();

		$errorModule = $location->getMeta('error');

		switch(get_class($e))
		{
			case 'AuthenticationError':

				$action = 'AuthenticationError';

				//$action = 'LogIn';
				//$errorModule = $location->getMeta('default');
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

		return $this->getAction($actionInfo['className'], (is_numeric($e->getCode())));
	}

	/**
	 * Loads the ioHandler into the system and returns its class name
	 *
	 * @access protected
	 * @param string|null $handlerName
	 * @return string
	 */
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

	/**
	 * Close down the request- this is the final thing that runs before the script finishes
	 *
	 * @access protected
	 */
	protected function close()
	{
		$this->requestHandler->close();

		//no more database access after this point!
		DatabaseConnection::close();
	}

}

?>