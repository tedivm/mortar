<?php

class RequestWrapperInstaller extends RequestWrapper
{
	public static $ioHandlerType = 'Http';
	protected $ioHandler;

	protected $currentLocation;

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
			throw new BentoError($className . ' should implement interface ActionInterface');

		// Create the class
		$action = new $className($argument, $this->getHandler());

		return $action;
	}

	protected function handleError($e)
	{

	}



	protected function close()
	{
		$this->requestHandler->close();


	}

	protected function loadActionClass()
	{
		$classname = 'BentoBaseActionInstall';

		$config = Config::getInstance();
		$path = $config['path']['modules'] . 'BentoBase/actions/Install.class.php';

		if(!class_exists($classname, false))
		{
			if(!file_exists($path))
				throw new ResourceNotFoundError('Unable to load action file: ' . $path);
			include($path);
		}

		return array('className' => $classname, 'argument' => null);
	}



}











?>