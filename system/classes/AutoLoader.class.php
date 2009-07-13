<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 */

/**
 * This autoloader works by building an index of classes and their filenames.
 *
 * @package System
 */
class AutoLoader
{
	/**
	 * this contains an associative array with the class name being the index and the path to that class being the
	 * value.
	 *
	 * @var array
	 */
	protected static $classIndex;

	/**
	 * This function is called by the system when it is unable to locate a class.
	 *
	 * @param string $classname
	 * @return bool
	 */
	static function loadClass($classname)
	{
		if(class_exists($classname, false))
			return true;

		if(is_null(self::$classIndex))
			self::createClassIndex();

		// if the class name doesn't exist clear out the cache and reload.
		if(!isset(self::$classIndex[$classname]))
		{
			Cache::clear('system', 'classLookup');
			Cache::clear('modules');
			self::$classIndex = null;
			self::createClassIndex();
		}

		if(isset(self::$classIndex[$classname]) && is_readable(self::$classIndex[$classname]))
		{
			include(self::$classIndex[$classname]);
			return true;
		}else{
			return false;
		}
	}

	/**
	 * This function creates the index array used by the loadClass function. It relies on numerous helper functions and
	 * caches their results individually.
	 *
	 * @cache system classLookup *folder
	 * @cache modules *moduleName classLookup
	 */
	static protected function createClassIndex()
	{
		$classArray = array();
		$config = Config::getInstance();

		foreach(array('interfaces', 'abstracts', 'mainclasses', 'library', 'moduleSupport') as $folder)
		{
			$cache = new Cache('system', 'classLookup', $folder);
			$lookupClasses = $cache->getData();
			if($cache->isStale())
			{
				$lookupClasses = ($folder == 'moduleSupport') ? self::loadModuleSupport($config['path']['mainclasses'])
																: self::loadDirectory($config['path'][$folder]);
				$cache->storeData($lookupClasses);
			}
			$classArray[] = $lookupClasses;
		}

		$packageList = new PackageList();
		$installedPackages = $packageList->getInstalledPackages();

		foreach($installedPackages as $package)
		{
			$packageInfo = new PackageInfo($package);
			$moduleName = $packageInfo->getName();

			$cache = new Cache('modules', $moduleName, 'classLookup');
			$lookupClasses = $cache->getData();

			if($cache->isStale())
			{
				$lookupClasses = self::loadModule($package);
				$cache->storeData($lookupClasses);
			}
			$classArray[] = $lookupClasses;
		}

		$classes = call_user_func_array('array_merge', $classArray);

		// the active page class exists in the page file
		$classes['ActivePage'] = $classes['Page'];

		self::$classIndex = $classes;
	}

	/**
	 * This function returns an array of classes and paths from the system/classes/modelSupport folder and subfolder. As
	 * its argument it takes in the system/classes path.
	 *
	 * @param string $basePath
	 * @return array
	 */
	static protected function loadModuleSupport($basePath)
	{
		$moduleFolders = array('actions' => 'ModelAction',
								'actions/LocationBased' => 'ModelActionLocationBased',
								'converters' => 'ModelTo',
								'Listings' => 'none',
								'Forms' => 'none');
		$classes = array();
		foreach($moduleFolders as $folder => $label)
		{
			$path = $basePath . 'modelSupport/' . $folder . '/';
			$unfilteredClasses = self::loadDirectory($path);
			$namePrefix = '';

			if($label != 'none')
				$namePrefix .= $label;

			foreach($unfilteredClasses as $name => $path)
			{
				$classes[$namePrefix . $name] = $path;
			}
		}

		return $classes;
	}

	/**
	 * This function loads the classes and paths from each directory inside a module and then adds the appropriate class
	 * prefix to the name.
	 *
	 * @param string|int $module
	 * @return array
	 */
	static protected function loadModule($module)
	{
		$module = new PackageInfo($module);
		$basePath = $module->getPath();
		$moduleName = $module->getName();
		$moduleFolders = array('actions' => 'Action',
								'models' => 'Model',
								'classes' => 'none',
								'plugins' => 'Plugin');
		$classes = array();
		foreach($moduleFolders as $folder => $label)
		{
			$path = $basePath . $folder . '/';
			$unfilteredClasses = self::loadDirectory($path);
			$namePrefix = $moduleName;

			if($label != 'none')
				$namePrefix .= $label;

			foreach($unfilteredClasses as $name => $path)
				$classes[$namePrefix . $name] = $path;
		}
		return $classes;
	}

	/**
	 * This function is used by all of the index creation function to handle the actual crawling of each directory. As
	 * its argument it takes in a directory and it returns an array with the file shortname (the part before .class.php)
	 * and the path to the file. It does not load or open the file in anyway so it can not verify the class name- any
	 * different naming standard should put a wrapper around this class.
	 *
	 * @param string $directory
	 * @return array
	 */
	static protected function loadDirectory($directory)
	{
		$directory = rtrim($directory, '/') . '/';
		$directory .= '*.*.php';
		$paths = glob($directory);
		$classes = array();
		foreach($paths as $path)
		{

			$tmpArray = explode('/', $path);
			$filename = array_pop($tmpArray);
			$tmpArray = explode('.', $filename);
			$classname = array_shift($tmpArray);
			$classes[$classname] = $path;
		}

		return $classes;
	}
}


spl_autoload_register(array('AutoLoader', 'loadClass'));

?>