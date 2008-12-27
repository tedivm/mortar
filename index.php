<?php
define('START_TIME', microtime(true));
define('BASE_PATH', dirname(__FILE__) . '/');
define('DISPATCHER', array_pop(explode('/', __FILE__)));

// Developer Constants
define('DEBUG', 0);	// 3, 2, 1, 0- info, warning, error, none
// 3 - info, warning, error
// 2 - warning, error
// 1 - error
// 0 - none, for production environments

define('IGNOREPERMISSIONS', false);	//FOR TESTING ONLY!!!!
// This was placed in while testing the permissions code during the early creation phases
// It still comes in handy when testing those things, but if turned on in a development environment
// there would be obvious problems.

define('BENCHMARK', false);
// This will pop some useful information onto the end of each run
// This breaks the formatting of pretty much everything, especially json and other things

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

require('system/classes/exceptions.class.php');
require('system/classes/config.class.php');
require('system/library/IniFile.class.php');
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

	$e->getCode();


	$moduleInfo = new ModuleInfo($errorModule);
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

	$benchmarkString = '';
	if(class_exists('ActiveUser', false) && !INSTALLMODE)
	{

		$user = ActiveUser::getInstance();
		$benchmarkString .= 'Active User: '. $user->getName() . PHP_EOL;
	}


	$benchmarkString .=  'Script Runtime (seconds): ' . $runtime . PHP_EOL;
	if(function_exists('getrusage'))
	{
		$dat = getrusage();
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
