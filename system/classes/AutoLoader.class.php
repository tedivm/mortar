<?php
/**
 * Mortar
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
	protected static $baseDirectories = array('interfaces',
											  'abstracts',
											  'mainclasses',
											  'library',
											  'thirdparty',
											  'View' => 'views');

	protected static $loadedModules = array();

	protected static $extraClassDirectories = array('modelSupport/actions' => 'ModelAction',
										'modelSupport/actions/LocationBased' => 'ModelActionLocationBased',
										'modelSupport/Converters' => 'ModelTo',
										'modelSupport/Listings' => 'none',
										'modelSupport/Forms' => 'none',
										'modelSupport/DisplayLists' => 'none',
										'Orm' => 'Orm',
										'InputHandlers' => 'none',
										'cacheHandlers' => 'cacheHandler',
										'RequestWrapper/IOProcessors' => 'IOProcessor',
										'templateSupport' => 'TagBox');

	protected static $extraLibraryDirectories = array('Form/Converters' => 'FormTo',
										'Form/ValidationRules' => 'FormValidation',
										'Form' => 'Form',
										'Filters' => 'Filter');

	protected static $thirdPartyIncludes;

	static function registerAutoloader()
	{
		spl_autoload_register(array(new self, 'loadClass'));
		if(!isset(self::$classIndex))
			self::createClassIndex();

		$config = Config::getInstance();
		self::$thirdPartyIncludes = $config['path']['thirdparty'];
	}

	static function internalClassExists($classname)
	{
		return isset(self::$classIndex[$classname]);
	}


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

		if(!isset(self::$classIndex))
			self::createClassIndex();

		// if the class name doesn't exist clear out the cache and reload.
		if(!isset(self::$classIndex[$classname]))
		{
			if(self::loadExternal($classname))
				return true;

			Cache::clear('system', 'autoloader');
			self::$classIndex = null;
			self::createClassIndex();
		}

		if(isset(self::$classIndex[$classname]))
		{
			include(self::$classIndex[$classname]);
			return true;
		}else{
			return false;
		}
	}

	static function loadExternal($class)
	{

		if(strpos($class, 'Twig') === 0) //&& Twig_Autoloader::autoload($class))
		{
			$twigPath = self::$thirdPartyIncludes . 'Twig' . '/../'.str_replace('_', '/', $class).'.php';
			include($twigPath);
			//require dirname(__FILE__).'/../'.str_replace('_', '/', $class).'.php';
			return true;
		}

		return class_exists($class);
	}


	static function addModule($moduleName)
	{
		if(in_array($moduleName, self::$loadedModules))
			return;

		$classes = self::loadModule($moduleName);
		if(is_array($classes) && count($classes) > 0)
			self::$classIndex = array_merge(self::$classIndex, $classes);
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
		$cache = new Cache('system', 'autoloader', 'classindex');
		$cacheData = $cache->getData();

		if($cache->isStale())
		{
			$classArray = array();
			$config = Config::getInstance();
			self::$loadedModules = array();

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

			$cacheData['loadedModules'] = self::$loadedModules;
			$cacheData['classes'] = $classes;
			$cache->storeData($cacheData);
		}

		self::$loadedModules = $cacheData['loadedModules'];
		self::$classIndex = $cacheData['classes'];
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

		foreach(self::$baseDirectories as $index => $folder)
		{
			$label = is_numeric($index) ? 'none' : $index;
			$lookupClasses = self::loadDirectoryAndFilter('', array($config['path'][$folder] => $label));

			//$lookupClasses = self::loadDirectory($config['path'][$folder]);
			$classArray[] = $lookupClasses;
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
			$lookupClasses = self::loadModule($package);
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
		$config = Config::getInstance();

		$classes = array();
		$classes[] = self::loadDirectoryAndFilter($config['path']['mainclasses'], self::$extraClassDirectories);
		$classes[] = self::loadDirectoryAndFilter($config['path']['library'], self::$extraLibraryDirectories);

		$outputControllers = self::loadDirectoryAndFilter($config['path']['mainclasses'],
									array('RequestWrapper/OutputControllers' => 'none'));

		$outputClasses = array();
		foreach($outputControllers as $outputBaseName => $classPath)
			$outputClasses[$outputBaseName . 'OutputController'] = $classPath;

		$classes[] = $outputClasses;

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
		if(in_array($module, self::$loadedModules))
			return array();

		self::$loadedModules[] = $module;

		$module = new PackageInfo($module);
		$basePath = $module->getPath();
		$moduleName = $module->getName();
		$moduleFolders = array('actions' => 'Action',
								'models' => 'Model',
								'classes' => 'none',
								'plugins' => 'Plugin');
		$classes = array();

		$path = $basePath . 'classes/*';
		$directories = glob($path, GLOB_ONLYDIR);

		foreach($directories as $directory)
		{
			$directory = rtrim($directory, '/');
			$tmpArray = explode('/', $directory);
			$directory = array_pop($tmpArray);
			$label = $directory;
			$moduleFolders['classes/' . $directory] = $label;
		}

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

Autoloader::registerAutoloader();
?>