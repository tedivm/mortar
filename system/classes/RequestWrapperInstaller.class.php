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
		try{

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


			if(!is_object($action))
				throw new BentoError('Can not run invalid action.');

		}catch(Exception $e){
			throw new BentoError('Unable to load action handler.');
		}
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
		switch(get_class($e))
		{
			case 'ResourceNotFoundError':
				$output = 'These are not the resources you are looking for.';
				break;
			default:
				var_dump($e);
				$output = 'There was an error with the installer.';
				break;
		}

		echo $output;
		exit(); // Inappropriate bailout! Way to handle errors, jackass! (note to self)
	}

	/**
	 * This class makes sure the action class is loaded into the system and returns its name
	 *
	 * @access protected
	 * @return array
	 */
	protected function loadActionClass()
	{
		$query = Query::getQuery();
		if(isset($query['action']))
		{
			$action = preg_replace('/[^a-zA-Z0-9\s]/', '', $query['action']);

			if($action == 'Minify')
				$action = 'JsSub';
		}else{
			$action = 'Install';
		}

		$classname = 'InstallerAction' . $action;
		$config = Config::getInstance();
		$path = $config['path']['modules'] . 'Installer/actions/' . $action . '.class.php';

		if(!class_exists($classname, false))
		{
			if(!file_exists($path))
				throw new ResourceNotFoundError('Unable to load action file: ' . $path);
			include($path);
		}

		return array('className' => $classname, 'argument' => $this->ioHandler);
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