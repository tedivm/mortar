<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This autoloader sucks. This is the first thing against the wall when php 5.3 comes out
 *
 * @package MainClasses
 */
class AutoLoader
{
	/**
	 * List of modules to check for classes
	 *
	 * @access protected
	 * @see import()
	 * @static
	 * @var array
	 */
	static protected $packages = array();

	/**
	 * Add a new module to the list of modules checked when the autoloader hits
	 *
	 * @access public
	 * @see $packages, loadBentoConfigPath()
	 * @static
	 * @param $string $module
	 */
	static public function import($module)
	{
		if(!in_array($module, self::$packages))
			self::$packages[] = $module;
	}

	/**
	 * Attempt to load the class from the library.
	 *
	 * @access public
	 * @see loadBentoConfigPath()
	 * @static
	 * @param string $className
	 */
	static public function loadBentoLibrary($className)
	{
		self::loadBentoConfigPath('library', $className);
	}

	/**
	 * Attempt to load the class from the abstract classes.
	 *
	 * @access public
	 * @see loadBentoConfigPath()
	 * @static
	 * @param string $className
	 */
	static public function loadBentoAbstract($className)
	{
		self::loadBentoConfigPath('abstracts', $className);
	}

	/**
	 * Attempt to load the class from the MainClasses group.
	 *
	 * @access public
	 * @see loadBentoConfigPath()
	 * @static
	 * @param string $className
	 */
	static public function loadBentoClasses($className)
	{
		self::loadBentoConfigPath('mainclasses', $className);
	}

	/**
	 * Attempt to load the class from the library.
	 *
	 * @access public
	 * @see import, loadBentoConfigPath(), self::packages
	 * @static
	 * @param string $className
	 */
	static public function loadActiveModule($className)
	{
		$packages = self::$packages;
		$info = InfoRegistry::getInstance();
		$packagePath = $info->Configuration['path']['modules'];

		/*
		I know how ridiculous a forloop is in an autoincluder, so please don't judge.
		Remember, this is just until we get namespaces.
		*/
		foreach($packages as $package)
		{
			if(strpos($className, $package) === 0)
			{
				$path = $packagePath . $package . '/';
				$fileName = substr($className, strlen($package)) . '.class.php';

				if(strpos($className, $package . 'Action') === 0)
				{
					$fileName = substr($className, strlen($package) + 6) . '.class.php';
					$pathToCheck = $path . 'actions/' . $fileName;
					if(self::checkDirectory($pathToCheck, $className))
						return true;
				}

				$dirs = array('classes', 'library');

				foreach($dirs as $directory)
				{
					$pathToCheck = $path . $directory . '/' . $fileName;
					if(self::checkDirectory($pathToCheck, $className))
						return true;
				}

			}//if(strpos($className, $info->Runtime['package']) === 0)
		}//foreach(self::$packages as $package)
	}

	/**
	 * Attempt to load the interface from the interface group.
	 *
	 * @access public
	 * @see import, loadBentoConfigPath(), self::packages
	 * @static
	 * @param string $className
	 */
	static public function loadBentoInterface($className)
	{
		$config = Config::getInstance();
		self::checkDirectory($config['path']['interfaces'] . $className . '.interface.php', $className);
	}

	/**
	 * This method is called last by the autoloader to throw an error using the system exceptions.
	 *
	 * @access public
	 * @static
	 * @param string $className
	 */
	static public function loadError($className)
	{
		try{
			throw new BentoNotice('Unable to include class: ' . $className);
		}catch (Exception $e){

		}
	}

	/**
	 * This method passes requests from the various 'loadBento' classes to the checkDirectory method
	 *
	 * @access protected
	 * @static
	 * @param string $type
	 * @param string $className
	 */
	static protected function loadBentoConfigPath($type, $className)
	{
		$config = Config::getInstance();
		self::checkDirectory($config['path'][$type] . $className . '.class.php', $className);
	}


	/**
	 * Checks a directory to see if it contains a class
	 *
	 * @access protected
	 * @static
	 * @param string $directory
	 * @param string $classname
	 * @return bool
	 */
	static protected function checkDirectory($directory, $classname)
	{
		try{
			if(is_readable($directory))
			{
				include($directory);
			}

			if(class_exists($classname, false))
				return true;

		}catch (Exception $e){

		}
		return false;
	}

}

spl_autoload_register(array('AutoLoader', 'loadBentoLibrary'));
spl_autoload_register(array('AutoLoader', 'loadBentoClasses'));
spl_autoload_register(array('AutoLoader', 'loadBentoAbstract'));
spl_autoload_register(array('AutoLoader', 'loadActiveModule'));
spl_autoload_register(array('AutoLoader', 'loadBentoInterface'));
spl_autoload_register(array('AutoLoader', 'loadError'));


?>