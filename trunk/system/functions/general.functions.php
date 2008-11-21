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
	$dbconnector = DB_Connection::getInstance();
	$db = $dbconnector->getConnection($database_name);
	return $db;
}

function autoloadLibrary($class_name)
{
	$config = Config::getInstance();
	$class_filename = $config['path']['library'] . $class_name . '.class.php';
	
	try{
		if(is_readable($class_filename))
		{
			include($class_filename);
		}else{
//			throw new BentoNotice('Unable to include file: ' . $class_filename);
		}
		
	}catch (Exception $e){
		
	}
	
}

function autoloadMain($class_name)
{
	$config = Config::getInstance();
	$class_filename = $config['path']['mainclasses'] . $class_name . '.class.php';
	
	try{
		if(is_readable($class_filename))
		{
			include($class_filename);
		}else{
//			throw new BentoNotice('Unable to include file: ' . $class_filename);
		}
		
	}catch (Exception $e){
		
	}
	
} 

function autoloadError($class_name)
{
	try{
		throw new BentoNotice('Unable to include class: ' . $class_name);
	}catch (Exception $e){
		
	}
	
} 
// This is temporary until the new namespaces stuff is out
spl_autoload_register('autoloadLibrary');
spl_autoload_register('autoloadMain');
spl_autoload_register('autoloadError');

function load_helper($package, $class)
{
	$config = Config::getInstance();
		
	$classname = $package . $class;

	if(!class_exists($classname, false))
	{
		$class_path = $config['path']['packages'] . $package . '/classes/' . $class .'.helper_class.php';

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

function staticHack($className, $memberName)
{

	if (!is_string($className)) $className = get_class($className);
	
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

?>