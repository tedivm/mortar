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
 * This class overloads some of the original RequestWrapper functions for the installation process. Primarily, this
 * means cutting out anything that needs database access.
 *
 * @package System
 * @subpackage RequestWrapper
 */
class RequestWrapperInstaller extends RequestWrapper
{
	/**
	 * Same as parent, but we have to put this there to prevent an error due to php's static handling
	 *
	 * @static
	 * @var string
	 */
	public static $ioHandlerType = 'Http';

	/**
	 * Returns the installer actions
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
			throw new BentoError($className . ' should implement interface ActionInterface');

		// Create the class
		$action = new $className($argument, $this->getHandler());

		return $action;
	}

	/**
	 * Handles errors for the installer
	 *
	 * @access protected
	 * @param exception $e
	 */
	protected function handleError($e)
	{

	}

	/**
	 * Final code run for the request
	 *
	 * @access protected
	 */

	/*
	protected function close()
	{
		$this->ioHandler->close();


	}
*/
	/**
	 * This class makes sure the action class is loaded into the system and returns its name
	 *
	 * @access protected
	 * @return array
	 */
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

	/**
	 * In the parent class this function logs request details to the database, but the installer doesn't always have
	 * database access so logging is disabled.
	 *
	 * @return bool
	 */
	protected function logRequest()
	{
		return true;
	}
}

?>