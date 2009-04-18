<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.BentoBase.org
 */


/**
* Returns the database connection
*
* @return Mysql_Base
*/
function db_connect($database_name = 'default_read_only')
{
	return dbConnect($database_name);
}

function dbConnect($database_name = 'default_read_only')
{
	depreciationWarning();
	return DatabaseConnection::getConnection($database_name);
}

function load_helper($package, $class)
{
	depreciationWarning();
	$config = Config::getInstance();

	$classname = $package . $class;

	if(!class_exists($classname, false))
	{
		$class_path = $config['path']['modules'] . $package . '/classes/' . $class .'.helper_class.php';

		if(file_exists($class_path))
		{
			include($class_path);
		}else{
			return false;
		}
	}

	return new $classname();
}

function stripslashes_deep($value)
{
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
		stripslashes($value);
	return $value;
}

function deltree($file)
{
	$file = rtrim($file, ' /');
	if (is_dir($file)) {
		$files = glob($file.'/*');
		foreach($files as $sf){
			if(is_dir($sf) && !is_link($sf)) {
				deltree($sf);
			}else{
				unlink($sf);
			}
		}
		rmdir($file);
	}
}

function loadHook($LocationId, $name)
{
	$hook = new Hook($id, $name);
	return $hook->plugins;
}


function importClass($classname, $path, $basePath = null, $require = false)
{
	if(!class_exists($classname, false))
	{
		if(isset($basePath))
		{
			$config = Config::getInstance();
			if(isset($config['path'][$basePath]))
				$path = $config['path'][$basePath] . $path;
		}

		if(file_exists($path))
			include($path);

		if(class_exists($classname, false))
		{
			return $classname;
		}else{
			if($require)
				throw new BentoError('Unable to load class ' . $classname . ' at ' . $path);

			return false;
		}

	}else{
		return $classname;
	}
}

function importModel($modelName)
{
	$modelInfo = ModelRegistry::getHandler($modelName);
	return importFromModule($modelInfo['name'], $modelInfo['module'], 'Model', true);
}

function importFromModule($name, $module, $classType, $require = false)
{
	$moduleFolders = array('abstract' => 'abstracts',
		'abstract' => 'abstracts',
		'actions' => 'actions',
		'action' => 'actions',
		'class'  => 'classes',
		'classes'  => 'classes',
		'hook'  => 'hooks',
		'hooks'  => 'hooks',
		'interfaces'  => 'interfaces',
		'interface'  => 'interfaces',
		'library'  => 'library',
		'model' => 'models',
		'plugin' => 'plugins',
		'plugins' => 'plugins');

	if(isset($moduleFolders[strtolower($classType)]))
	{
		$classDivider = ucwords(strtolower($classType));
	}elseif($classDivider = array_search(strtolower($classType), $moduleFolders)){
		$classDivider = ucwords($classDivider);
	}

	$packageInfo = new PackageInfo($module);
	$path = $packageInfo->getPath() . $moduleFolders[strtolower($classType)] . '/' . $name . '.class.php';
	$className = $packageInfo->getName() . $classDivider . $name;
	return importClass($className, $path, $require);
}

function staticHack($className, $memberName)
{
	if(is_object($className))
		$className = get_class($className);

	if(!class_exists($className, false))
		return;

	if (!@property_exists($className,$memberName)) {
		//trigger_error("Static property does not exist: $class::\$$var");
		//debug_callstack(); //This is just a wrapper for debug_backtrace() for HTML
		return;
	}

	//Store a reference so that the base data can be referred to
		//The code [[ return eval('return &'.$class.'::$'.$var.';') ]] does not work - can not return references...
		//To establish the reference, use [[ $ref=&get_static(...) ]]
	eval('$temp=&'.$className.'::$'.$memberName.';'); //using
	return $temp;
}

// first two arguments are $className and $functionName
function staticFunctionHack()
{
	$arguments = func_get_args();

	$className = array_shift($arguments);
	$functionName = array_shift($arguments);

	/* This dirty hack is brought to you by php failing at oop */
	if(is_callable(array($className, $functionName)))
	{
		return call_user_func_array(array($className, $functionName), $arguments);

	}else{
		try
		{
			throw new BentoError('static function ' . $functionName . 'not found in class ' . $className);
		}catch(Exception $e){

		}

		return false;
	}



}

function depreciationWarning()
{
	try
	{
		throw new BentoDepreciated('Function has been depreciated.');
	}catch(Exception $e){}
}

function depreciationError()
{
	throw new BentoDepreciated('Function has been depreciated.');
}

?>