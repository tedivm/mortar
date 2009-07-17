<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage RequestWrapper
 */

/**
 * This class acts as a main controller. It loads the appropriate input/output handler for the system,
 * the format output class, and the action to be run.
 *
 * @package System
 * @subpackage RequestWrapper
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
					$this->logRequest();
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
			$this->close();
		}catch(Exception $e){
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

				if(!isset($query['action']))
					$query['action'] = 'Default';

				if(!($actionInfo = $moduleInfo->getActions($query['action'])))
					throw new BentoError('Unable to load action for module ' . $query['module']);


				$argument = '';
				$className = importFromModule($actionInfo['name'], $query['module'], 'action', true);

				$query->save();
				return array('className' => $className, 'argument' => $argument);
			}

		}catch(Exception $e){
			throw new ResourceNotFoundError();
		}

		if($query['action'] != 'Add')
		{
			if(!isset($location) && isset($query['type']))
			{
				if(!$model = ModelRegistry::loadModel($query['type'], $query['id']))
					throw new ResourceNotFoundError();
			}else{
				if(!isset($location))
				{
					$site = ActiveSite::getSite();
					$location = $site->getLocation();
				}
				$model = $location->getResource();
			}

			if(!isset($query['action']))
				$query['action'] = 'Read';

			$actionInfo = $model->getAction($query['action']);
			if($actionInfo == false)
				return false;

			$className = $actionInfo['className'];
			$argument = $model;
			$query->save();

			return array('className' => $className, 'argument' => $argument);
		}


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
		$className = $actionInfo['className'];
		$argument = $model;

		if(!class_exists($className))
			throw new ResourceNotFoundError('Unable to load action class ' . $className, '405');


		if(($model instanceof LocationModel) && isset($location))
			$model->setParent($location);

		$query->save();
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
		if(!isset($className))
		{
			$actionClassInfo = $this->loadActionClass();

			if(!$actionClassInfo)
				throw new ResourceNotFoundError('Unable to load action class.');
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
		$query = Query::getQuery();
		$format = $query['format'];
		$outputController = $this->loadFormatHandler($action, $format);
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
	protected function loadFormatHandler($action, $format)
	{
		$formatFilter = $format . 'OutputController';
		if(!class_exists($formatFilter))
			return false;

		$outputController = new $formatFilter($this->getHandler());
		if(!$outputController->checkAction($action, $format))
			return false;

		return $outputController;
	}

	/**
	 * If the action class throws an exception and doesn't handle it internally this function takes care of assigning a
	 * an action handle and, if appropriate, logging the error to the database.
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
				RequestLog::logError($e, 1, 'BLOCKING');
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

		if(in_array($handlerName, $this->internalIoHandlers))
		{
			$className = 'IOProcessor' . $handlerName;
		}else{
			$className = $handlerName;
		}

		if(!class_exists($className))
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
		$this->ioHandler->close();



		if(BENCHMARK)
		{
			$requestInfo = new RequestStatistics();
			$requestInfo->saveToFile();
		}


		//no more database access after this point!
		DatabaseConnection::close();
	}

	/**
	 * This function logs request details to the database.
	 *
	 * @return bool
	 */
	protected function logRequest()
	{
		$query = Query::getQuery();
		$userId = ActiveUser::getUser()->getId();
		$location = isset($query['location']) ? $query['location'] : false;
		$action = isset($query['action']) ? $query['action'] : false;
		$iohandler = self::$ioHandlerType;
		$format = isset($query['format']) ? $query['format'] : false;
		$module = isset($query['module']) ? $query['module'] : false;
		$siteId = ActiveSite::getSite()->getId();
		return RequestLog::logRequest($userId, $siteId, $location, $module, $action, $iohandler, $format);
	}

}

?>