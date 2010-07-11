<?php
/**
 * Mortar
 *
 * A framework for developing modular applications.
 *
 * @package		Mortar
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
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

function stripslashes_deep($value)
{
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
		stripslashes($value);
	return $value;
}

function deltree($file)
{
	if(substr($file, 0, 1) !== '/')
		throw new CoreError('deltree function requires an absolute path.');

	$badCalls = array('/', '/*', '/.', '/..');
	if(in_array($file, $badCalls))
		throw new CoreError('deltree function does not like that call.');

	$file = rtrim($file, ' /');
	if(is_dir($file)) {
		$hiddenFiles = glob($file.'/.?*');
		$files = glob($file.'/*');
		$files = array_merge($hiddenFiles, $files);

		foreach($files as $filePath)
		{
			if(substr($filePath, -2, 2) == '/.' || substr($filePath, -3, 3) == '/..')
				continue;

			if(is_dir($filePath) && !is_link($filePath)) {
				deltree($filePath);
			}else{
				unlink($filePath);
			}
		}
		rmdir($file);
	}
}

function importClass($classname, $path, $basePath = null, $require = false)
{

	if(!class_exists($classname))
	{
		if(isset($basePath))
		{
			$config = Config::getInstance();
			if(isset($config['path'][$basePath]))
				$path = $config['path'][$basePath] . $path;
		}

		if(is_file($path))
			include($path);

		if(class_exists($classname, false))
		{
			return $classname;
		}else{
			if($require)
				throw new CoreError('Unable to load class ' . $classname . ' at ' . $path);

			return false;
		}

	}else{
		return $classname;
	}
}


/**
 *
 * @deprecated Being replaced by the PackageInfo "getClassName" method
 */
function importFromModule($name, $module, $classType, $require = false)
{
	if(!is_numeric($module))
		throw new CoreError('importFromModule function requires module to be an ID.');


	$packageInfo = PackageInfo::loadById($module);
	return $packageInfo->getClassName($classType, $name, $require);
}

function staticHack($className, $memberName)
{
	if(is_object($className))
		$className = get_class($className);

	if(!class_exists($className))
		return null;

	if(!property_exists($className, $memberName))
		return null;

	//Store a reference so that the base data can be referred to
	eval('$temp=&'.$className.'::$'.$memberName.';');
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
			throw new CoreError('static function ' . $functionName . ' not found in class ' . $className);
		}catch(Exception $e){

		}

		return false;
	}



}

function depreciationWarning()
{

}

function depreciationError()
{

}



/**
 * This takes an array and returns it as a string. It recursively turns element arrays into strings, increasing the
 * indentation at each level.
 *
 * @param array $array
 * @param int $level
 * @return string
 */
function arrayToString($array, $level = 0)
{
	$tab = str_repeat('   ', $level);
	$string = PHP_EOL;

	foreach($array as $name => $value)
	{
		$string .= $tab . $name . ': ';
		if(is_array($value))
		{
			$string .= arrayToString($value, $level + 1) . PHP_EOL;
		}else{
			$string .= $value . PHP_EOL;
		}
	}
	return $string;
}

?>