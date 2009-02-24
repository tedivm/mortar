<?php
define('START_TIME', microtime(true));
define('BASE_PATH', dirname(__FILE__) . '/');
define('DISPATCHER', array_pop(explode('/', __FILE__)));

// Developer Constants
define('DEBUG', 3);
// 4,		3,		2,			1,		0
// notices,	info, 	warning, 	error,	none
// strict - E_STRICT with no Bento error displays.
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


define('DISABLECACHE', true);
// This program is designed to take advantage of caching, and in many cases code was optimized with that in mind.
// Disabling caching is not recommended outside of development, which is why it is not an option in the interface.


if(BENCHMARK && function_exists('getrusage'))
{
	$startdat = getrusage();
	$startProcTime = $startdat["ru_utime.tv_usec"];
	unset($startdat);
}



switch(DEBUG)
{
	case 4:
		error_reporting(E_ALL);
		break;

	case 3:
		error_reporting(E_STRICT | E_ALL ^ E_NOTICE);
		break;

	case 2:
		error_reporting(E_ALL ^ E_NOTICE);
		break;

	case 1:
		error_reporting(E_ERROR | E_PARSE);
		break;

	case 0:
	default:
		error_reporting(0);
		break;

	case 'strict':
		error_reporting(E_STRICT);
		break;
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

require('system/classes/RequestWrapper.class.php');


require('system/classes/AutoLoader.class.php');

try{

	$config = Config::getInstance();


	$requestWrapperName = 'RequestWrapper';

	// If an error occured we may be looking at a pre-installation setup
	if($config->error)
	{
		// disable cache, since we can't load the settings for it anyways
		Cache::$runtimeDisable = true;

		// If the blockinstall file is there, or the install class file is not, we shouldn't attempt an install
		if(file_exists('.blockinstall')
			|| !file_exists($config['path']['modules'] . 'BentoBase/actions/Install.class.php'))
		{
			define('INSTALLMODE', false);
			throw new BentoError('Unable to load configuration file.');
		}else{
			// there is no block file, no configuration or block install file, so lets set this into install mode
			define('INSTALLMODE', true);
			$requestWrapperName = 'RequestWrapperInstaller';
			require('system/classes/RequestWrapperInstaller.class.php');
		}

	}else{
		// config loaded, so lets take the redundent step of setting install mode to false
		define('INSTALLMODE', false);

		// system timezone, defaulting to UTC
		$timezone = ($config['system']['timezone']) ? $config['system']['timezone'] : 'UTC';
		date_default_timezone_set($timezone);
	}

	$request = new $requestWrapperName();
	$request->main();

}catch (Exception $e){
	echo 'An uncaught error occured.';
}



if(BENCHMARK)
{
	$endtime = microtime(true);
	$runtime = $endtime - START_TIME;

	// moved this up here to make sure the statistics aren't affected too much by the benchmarking setup
	if(function_exists('getrusage'))
		$dat = getrusage();



	// at some point dump all the shit below this line into its own class.


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
	if(is_array($calls))
	{
		ksort($calls);

		foreach($calls as $name => $count)
		{
			$benchmarkString .= $count . ' ' . $name . PHP_EOL;
		}

	}
	$queryCount = 0;
	$queryArray = Mysql_Base::$query_array;

	if(is_array($queryArray))
	{
		ksort($queryArray);

		foreach($queryArray as $queryString => $count)
		{
			$queryList .= $count . ' ' . $queryString . PHP_EOL;
			$queryCount += $count;
		}
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
