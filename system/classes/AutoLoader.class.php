<?php

/*

This autoloader is full of fail, and will be replaced when php 5.3 comes out and we finally get namespaces

*/

class AutoLoader
{
	static protected $config;
	static protected $activeModule;
	
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
		$info = InfoRegistry::getInstance();
		
		if(!isset(self::$config))
			self::$config = Config::getInstance();
		
		if(strpos($className, $info->Runtime['package']) === 0)
		{
			$className = substr($className, strlen($info->Runtime['package']));
			self::checkDirectory(self::$config['path']['modules'] . $info->Runtime['package'] . '/classes/' . $className . '.class.php');
		}
	}	
	
	static public function loadError($className)
	{
		try{
			throw new BentoNotice('Unable to include class: ' . $class_name);
		}catch (Exception $e){
			
		}
	}	
	

	static protected function loadBentoConfigPath($type, $className)
	{
		if(!isset(self::$config))
			self::$config = Config::getInstance();
			
		self::checkDirectory(self::$config['path'][$type] . $className . '.class.php');
	}
	
		
	
	static protected function checkDirectory($directory)
	{
		try{
			if(is_readable($directory))
			{
				include($directory);
			}		
		}catch (Exception $e){
			
		}		
	}
	
}

spl_autoload_register(array('AutoLoader', 'loadBentoLibrary'));
spl_autoload_register(array('AutoLoader', 'loadBentoClasses'));
spl_autoload_register(array('AutoLoader', 'loadBentoAbstract'));
spl_autoload_register(array('AutoLoader', 'loadActiveModule'));
spl_autoload_register(array('AutoLoader', 'loadError'));


?>