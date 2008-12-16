<?php

/*

This autoloader is full of fail, and will be replaced when php 5.3 comes out and we finally get namespaces

*/

class AutoLoader
{
	static protected $config;
	static protected $activeModule;
	static protected $packages = array();

	static function import($package)
	{
		if(!in_array($package, self::$packages))
			self::$packages[] = $package;
	}

	static public function loadBentoLibrary($className)
	{
		self::loadBentoConfigPath('library', $className);
	}

	static public function loadBentoAbstract($className)
	{
		self::loadBentoConfigPath('abstracts', $className);
	}

	static public function loadBentoClasses($className)
	{
		self::loadBentoConfigPath('mainclasses', $className);
	}


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

	static public function loadError($className)
	{
		try{
			throw new BentoNotice('Unable to include class: ' . $className);
		}catch (Exception $e){

		}
	}


	static protected function loadBentoConfigPath($type, $className)
	{
		if(!isset(self::$config))
			self::$config = Config::getInstance();

		self::checkDirectory(self::$config['path'][$type] . $className . '.class.php', $className);
	}

	static public function loadBentoInterface($className)
	{
		if(!isset(self::$config))
			self::$config = Config::getInstance();

		self::checkDirectory(self::$config['path']['interfaces'] . $className . '.interface.php', $className);
	}



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