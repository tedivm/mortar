<?php

define('START_TIME', microtime(true));
define('BASE_PATH', dirname(__FILE__) . '/');
define('DEBUG', 3);	// 3, 2, 1- info, warning, error
define('DISPATCHER', 'index.php');
define('IGNOREPERMISSIONS', false);	//FOR TESTING ONLY!!!!
define('BENCHMARK', true);
define('DISABLECACHE', true);

if(BENCHMARK)
{
	if(function_exists('getrusage'))
	{
		$startdat = getrusage();
		$startProcTime = $startdat["ru_utime.tv_usec"];
		unset($startdat);
	}
}

require('system/classes/exceptions.class.php');
require('system/classes/config.class.php');
require('system/library/IniFile.class.php');
require('system/library/Post.class.php');
require('system/library/Get.class.php');
require('system/classes/displaymaker.class.php');
require('system/classes/database.class.php');
require('system/functions/general.functions.php');
require('system/engines/Engine.class.php');
require('system/classes/password.class.php');
require('system/classes/user.class.php');
require('system/interfaces/module.interfaces.php');
require('system/classes/permissions.class.php');
require('system/classes/page.class.php');
require('system/abstracts/ModuleBase.abstract.php');
require('system/classes/ModuleInfo.class.php');
require('system/abstracts/Plugin.abstract.php');
require('system/abstracts/action.class.php');
require('system/classes/hooks.class.php');
require('system/classes/Site.class.php');
require('system/classes/PackageInfo.class.php');

$config = Config::getInstance();

if($config->error && !file_exists('.blockinstall'))
{
	// prep for installations
	
	$engine = 'Install';
	$path['base'] =  BASE_PATH; 
	$path['engines'] = BASE_PATH . 'system/engines/';
	$path['library'] = BASE_PATH . 'system/library/';	
	$path['packages'] = BASE_PATH . 'modules/'; 
	 
	 
	 
	 
	 
	$config['path'] = $path;
	$config['engine'] = $engine;
	Cache::$runtimeDisable = true;
	
	define('INSTALLMODE', true);
	
	
}elseif($config->error){	
	define('INSTALLMODE', false);
	throw new BentoError('Unable to load engine: ' . $path);
	
}else{
	define('INSTALLMODE', false);
	
	$get = Get::getInstance();
	$engine = ((isset($get['engine'])) ? $get['engine'] : 'Html');

	$config['Url'] = $get['currentUrl'];
	$config['moduleId'] = $get['moduleId'];
	$config['siteId'] = $get['siteId'];
	
	
	
	
	$moduleInfo = new ModuleInfo($config['moduleId']);
	$config['module'] = $moduleInfo['Name'];
	
	$config['engine'] = (isset($get['engine'])) ? $get['engine'] : 'Html';
	$config['action'] = (isset($get['action'])) ? $get['action'] : 'Default';
	$config['id'] = $get['id'];

	/*
	echo '<BR> Module: ', $config['module'],
	'<BR> Module ID: ', $config['moduleId'],
	'<BR> Action: ', $config['action'],
	'<BR> ID: ', $config['id'],
	'<BR> Engine: ', $config['engine'],
	'<BR> SiteID: ', $config['siteId'], '<br>';
	*/
}

try {
	
	$path = $config['path']['engines'] . $engine . '.engine.php';
	$engineName = $engine . 'Engine';
	
	
	if(!file_exists($path))
		throw new BentoError('Unable to load engine: ' . $path);
	
	include($path);
	
	$engine = new $engineName($config['moduleId'], $config['action']);
	$engine->runModule();
	$output = $engine->display();	
	// two steps in case it throws an exception

	
}catch (Exception $e){
	
	switch (get_class($e))
	{
		case 'AuthenticationError':
			$action = 'AuthenticationError';
			break;
			
		case 'ResourceNotFoundError':
			$action = 'ResourceNotFound';
			break;

		case 'BentoWarning':
		case 'BentoNotice':
			// uncaught minor thing
			
		case 'BentoError':
		default:
			$action = 'TechnicalError';
			break;	
	}
	
	$e->getCode();
	
	$info = InfoRegistry::getInstance();
	$site = ActiveSite::get_instance();
	
	$moduleInfo = new ModuleInfo($site->location->meta('error'));
	

	$packageInfo = new PackageInfo($moduleInfo['Package']);
	
	//$packageInfo;
	
	$engine = new $engineName($moduleInfo->getId(), $action);
	$engine->runModule();
	$output = $engine->display();
	
	
}

echo $output;


$engine->finish();
if(BENCHMARK)
{
	$endtime = microtime(true);
	$runtime = $endtime - START_TIME;
	
	echo '<br><br>';	
	if(class_exists('ActiveUser', false) && !INSTALLMODE)
	{
		
		$user = ActiveUser::getInstance();
		//var_dump($user);
		echo '<br>Active User: '. $user->getName();		
	}
	
	
	echo '<br>Script Runtime (seconds): ' , $runtime;
	if(function_exists('getrusage'))
	{
		$dat = getrusage();
		echo '<br>CPU time (seconds): ', ($dat["ru_utime.tv_usec"] - $startProcTime)/ 1000000;
		//echo '<br>User Time Used (seconds): ', $dat["ru_utime.tv_sec"];	
	}
	echo '<br>Memory Usage: ', (int) (memory_get_usage() / 1024) . 'k';
	echo '<br>Peak Memory Usage: ', (int) (memory_get_peak_usage() / 1024) . 'k';

	if(function_exists('getrusage'))
	{
		echo '<br>Number of swaps: ', $dat["ru_nswap"];	
		echo '<br>Number of Page Faults: ', $dat["ru_majflt"];
	}
	
	echo '<br>Cache Calls: ' , Cache::$cacheCalls;
	echo '<br>Cache Returns: ' , Cache::$cacheReturns;	
	echo '<br>Query Count: ' , Mysql_Base::$query_count , '<br>';
	foreach(Mysql_Base::$query_array as $query)
	{
		echo $query , '<br>';
	}
}

?>
