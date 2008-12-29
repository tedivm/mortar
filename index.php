<?php
define('START_TIME', microtime(true));
define('BASE_PATH', dirname(__FILE__) . '/');
define('DISPATCHER', array_pop(explode('/', __FILE__)));

// Developer Constants
define('DEBUG', 0);	// 4, 3, 2, 1, 0- notices, info, warning, error, none
// The higher the number, the more information you get. This constant also controls the php error levels- 0 disables
// error reporting (useful for production environments), while 3 will give all errors and notices. For development
// purposes your best bet would be 2 or 3.

define('IGNOREPERMISSIONS', false);	//FOR TESTING ONLY!!!!
// This was placed in while testing the permissions code during the early creation phases
// It still comes in handy when testing those things, but if turned on in a development environment
// there would be obvious problems.

define('BENCHMARK', false);
// When enabled the system logs a variety of information. This informaion is saved in the temp/benchmark directory
// As each run of the system generates a new file, it is important not to keep this running on a live system
// This tool is useful in seeing what database queries and cache calls are made, how much memory and cpu time
// the script takes to run, and information about system settings during during that run.


define('DISABLECACHE', false);
// This program is designed to take advantage of caching, and in many cases code was optimized to with
// that in mind. Disabling caching is not recommended outside of development, which is why it is not
// an option in the interface.


if(BENCHMARK)
{
	if(function_exists('getrusage'))
	{
		$startdat = getrusage();
		$startProcTime = $startdat["ru_utime.tv_usec"];
		unset($startdat);
	}
}


switch(DEBUG)
{

	case 4:
		error_reporting(E_ALL);
		break;
	case 2:
		error_reporting(E_ERROR | E_PARSE | E_WARNING);
		break;
	case 1:
		error_reporting(E_ERROR | E_PARSE);
		break;
	case 0:
	default:
		error_reporting(0);
}

require('system/classes/exceptions.class.php');
require('system/classes/config.class.php');
require('system/library/IniFile.class.php');
require('system/classes/displaymaker.class.php');
require('system/classes/database.class.php');
require('system/functions/general.functions.php');
require('system/engines/Engine.class.php');
require('system/classes/password.class.php');
require('system/classes/user.class.php');
require('system/interfaces/ActionInterface.interface.php');
require('system/classes/permissions.class.php');
require('system/classes/page.class.php');
require('system/abstracts/ModuleBase.abstract.php');
require('system/abstracts/Plugin.abstract.php');
require('system/abstracts/action.class.php');
require('system/classes/Site.class.php');

require('system/classes/AutoLoader.class.php');


$config = Config::getInstance();

if($config->error && !file_exists('.blockinstall'))
{
	// prep for installations
	$engine = 'Install';
	$path['base'] =  BASE_PATH;
	$path['engines'] = BASE_PATH . 'system/engines/';
	$path['library'] = BASE_PATH . 'system/library/';
	$path['modules'] = BASE_PATH . 'modules/';
	$path['main_classes'] = BASE_PATH . 'system/classes/';


	$config['path'] = $path;
	$config['engine'] = $engine;
	Cache::$runtimeDisable = true;

	define('INSTALLMODE', true);


}elseif($config->error){
	define('INSTALLMODE', false);
	throw new BentoError('Unable to load engine: ' . $path);

}else{
	define('INSTALLMODE', false);
	$runtime = RuntimeConfig::getInstance();
	$engine = $runtime['engine'];
}

try {

	$path = $config['path']['engines'] . $engine . '.engine.php';
	$engineName = $engine . 'Engine';


	if(!file_exists($path))
		throw new BentoError('Unable to load engine: ' . $path);

	include($path);

	$engine = new $engineName();
	$engine->runModule();
	$output = $engine->display();
	// two steps in case it throws an exception


}catch (Exception $e){

	try{

		$info = InfoRegistry::getInstance();
		$site = ActiveSite::getInstance();
		$errorModule = $site->location->meta('error');

		switch (get_class($e))
		{
			case 'AuthenticationError':
				$action = 'LogIn';
				$errorModule = 1;
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




		$moduleInfo = new ModuleInfo($errorModule);
		$packageInfo = new PackageInfo($moduleInfo['Package']);

	//	var_dump($moduleInfo);
		//$moduleInfo['locationId']
		//$packageInfo;
		$engine = new $engineName($moduleInfo['locationId'], $action);
		$engine->runModule();
		$output = $engine->display();

	}catch(Exception $e){
		$moduleInfo = new ModuleInfo($errorModule);

		$engine = new $engineName($moduleInfo['locationId'], 'TechnicalError');
		$engine->runModule();
		$output = $engine->display();


	}




}

echo $output;

$engine->finish();
if(BENCHMARK)
{
	$endtime = microtime(true);
	$runtime = $endtime - START_TIME;

	// moved this up here to make sure the statistics aren't affected too much by the benchmarking setup
	if(function_exists('getrusage'))
		$dat = getrusage();

	$benchmarkString = '';

	$siteInfo = ActiveSite::getInstance();
	$runtimeConfig = RuntimeConfig::getInstance();

	$benchmarkString .= 'Site Name: ' . $siteInfo->name . PHP_EOL;
	$benchmarkString .= 'Location: ' . $runtimeConfig['action'] . PHP_EOL;
	$benchmarkString .= 'Action: ' . $runtimeConfig['currentLocation'] . PHP_EOL;
	$benchmarkString .= 'Module: ' . $runtimeConfig['package'] . PHP_EOL;
	$benchmarkString .= 'ID: ' . $runtimeConfig['id'] . PHP_EOL;
	$benchmarkString .= 'Engine: ' . $runtimeConfig['engine'] . PHP_EOL;


	if(class_exists('ActiveUser', false) && !INSTALLMODE)
	{
		$user = ActiveUser::getInstance();
		$benchmarkString .= 'Active User: '. $user->getName() . PHP_EOL;
	}


	$benchmarkString .=  'Script Runtime (seconds): ' . $runtime . PHP_EOL;
	if(isset($dat))
	{
		$benchmarkString .=  'CPU time (seconds): ' . ($dat["ru_utime.tv_usec"] - $startProcTime)/ 1000000;
		$benchmarkString .=  PHP_EOL;
		//echo '<br>User Time Used (seconds): ', $dat["ru_utime.tv_sec"];
	}
	$benchmarkString .=  'Memory Usage: ' . (int) (memory_get_usage() / 1024) . 'k';
	$benchmarkString .= PHP_EOL;

	$benchmarkString .=  'Peak Memory Usage: ' . (int) (memory_get_peak_usage() / 1024) . 'k';
	$benchmarkString .= PHP_EOL;

	if(function_exists('getrusage'))
	{
		$benchmarkString .=  'Number of swaps: ' . $dat["ru_nswap"] . PHP_EOL;
		$benchmarkString .=  'Number of Page Faults: '. $dat["ru_majflt"] . PHP_EOL;
	}

	$benchmarkString .=  'Cache Calls: ' . Cache::$cacheCalls . PHP_EOL;
	$benchmarkString .=  'Cache Returns: ' . Cache::$cacheReturns . PHP_EOL;
	$calls = Cache::getCalls();
	ksort($calls);
	foreach($calls as $name => $count)
	{
		$benchmarkString .= $count . ' ' . $name . PHP_EOL;
	}


	$queryCount = 0;
	$queryArray = Mysql_Base::$query_array;
	if(is_array($queryArray));
	ksort($queryArray);

	foreach($queryArray as $queryString => $count)
	{
		$queryList .= $count . ' ' . $queryString . PHP_EOL;
		$queryCount += $count;
	}

	$benchmarkString .= 'Query Count: ' . $queryCount . PHP_EOL;
	$benchmarkString .= $queryList;

	$config = Config::getInstance();
	if(!$config->error)
	{
		$fileName = date("ymdHis") . '.txt';

		$benchmarkPath = $config['path']['temp'] . 'benchmarks/';

		if(!is_dir($benchmarkPath))
			mkdir($benchmarkPath, 0755, true);

		file_put_contents($config['path']['temp'] . 'benchmarks/' . $fileName, $benchmarkString);
	}
}

?>
