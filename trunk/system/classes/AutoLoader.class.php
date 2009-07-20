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
	 * This array contains all of the system directories that the autoloader will crawl for classes. This doesn't
	 * include packages or subfolders, although those get indexed as well.
	 *
	 * @var array
	 */
	protected static $baseDirectories = array('interfaces', 'abstracts', 'mainclasses', 'library', 'thirdparty');

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

		$packageClasses = self::loadPackageClasses();
		$coreClasses = self::loadCoreClasses();
		$systemClasses = self::loadExtraSystemClasses();
		$classArray = array_merge($classArray,
								$packageClasses,
								$coreClasses,
								$systemClasses);

		$classes = call_user_func_array('array_merge', $classArray);

		// the active page class exists in the page file
		$classes['ActivePage'] = $classes['Page'];
		self::$classIndex = $classes;
	}


	/**
	 * This function looks through all of the system directories and loads the classes from it.
	 *
	 * @cache system classLookup *folder
	 * @return array
	 */
	static protected function loadCoreClasses()
	{
		$classArray = array();
		$config = Config::getInstance();
		$cache = new Cache('system', 'classLookup', 'coreIndex');
		$classArray = $cache->getData();
		if($cache->isStale())
		{
			foreach(self::$baseDirectories as $folder)
			{
				$lookupClasses = self::loadDirectory($config['path'][$folder]);
				$classArray[] = $lookupClasses;
			}
			$cache->storeData($classArray);
		}

		return $classArray;
	}

	/**
	 * This function runs through each installed package and loads all of the classes from it.
	 *
	 * @cache modules *moduleName classLookup
	 * @return array
	 */
	static protected function loadPackageClasses()
	{
		$classArray = array();
		$packageList = new PackageList();
		$installedPackages = $packageList->getInstalledPackages();

		foreach($installedPackages as $package)
		{
			$cache = new Cache('modules', $package, 'classLookup');
			$lookupClasses = $cache->getData();
			if($cache->isStale())
			{
				$lookupClasses = self::loadModule($package);
				$cache->storeData($lookupClasses);
			}
			$classArray[] = $lookupClasses;
		}
		return $classArray;
	}

	/**
	 * This function loads all of the subfolders (and their subfolders) in the system/classes directory.
	 *
	 * @return array
	 */
	static protected function loadExtraSystemClasses()
	{
		$cache = new Cache('system', 'classLookup', 'extraSystemClasses');
		$classes = $cache->getData();
		if($cache->isStale())
		{
			$config = Config::getInstance();
			$moduleFolders = array('modelSupport/actions' => 'ModelAction',
									'modelSupport/actions/LocationBased' => 'ModelActionLocationBased',
									'modelSupport/converters' => 'ModelTo',
									'modelSupport/Listings' => 'none',
									'modelSupport/Forms' => 'none',
									'InputHandlers' => 'none',
									'cacheHandlers' => 'cacheHandler',
									'RequestWrapper/IOProcessors' => 'IOProcessor');

			$classes = array(self::loadDirectoryAndFilter($config['path']['mainclasses'], $moduleFolders));
			$outputControllers = self::loadDirectoryAndFilter($config['path']['mainclasses'],
										array('RequestWrapper/OutputControllers' => 'none'));

			$outputClasses = array();
			foreach($outputControllers as $outputBaseName => $classPath)
				$outputClasses[$outputBaseName . 'OutputController'] = $classPath;

			$classes[] = $outputClasses;
			$cache->storeData($classes);
		}
		return $classes;
	}

	/**
	 * This function takes in a path and an array of subpaths mapped to class prefixes. All of the elements get looped
	 * through, returning class information from the loadDirectory function. It then loops through and applies the
	 * prefix to all of the class names generated from tee loadDirectory function, unless that prefix is the string
	 * "none"
	 *
	 * @cache system classLookup *path
	 * @param string $basePath
	 * @param array $moduleFolders
	 * @return array
	 */
	static protected function loadDirectoryAndFilter($basePath, $moduleFolders)
	{
		$classes = array();
		foreach($moduleFolders as $folder => $label)
		{
			$path = $basePath . $folder . '/';
			$lookupClasses = array();
			$unfilteredClasses = self::loadDirectory($path);

			if($label != 'none')
			{
				foreach($unfilteredClasses as $name => $path)
					$lookupClasses[$label . $name] = $path;
			}else{
				$lookupClasses = $unfilteredClasses;
			}

			$classes = array_merge($classes, $lookupClasses);
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