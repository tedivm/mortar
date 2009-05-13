<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

define('START_TIME', microtime(true));
define('BASE_PATH', dirname(__FILE__) . '/');
define('DISPATCHER', array_pop(explode('/', __FILE__)));

require('data/profiles/runtime.php');


if(BENCHMARK && function_exists('getrusage'))
{
	$startdat = getrusage();
	$startProcTime = $startdat["ru_utime.tv_usec"];
	define(START_PROCESS_TIME, $startdat['ru_utime.tv_usec']);
	unset($startdat);
}

// Error Handling Setup

switch(DEBUG)
{
	case 4:
		$errorLevel = E_ALL;
		break;

	case 3:
		$errorLevel = E_ALL ^ E_NOTICE;
		break;

	case 2:
		$errorLevel = E_ALL ^ E_NOTICE;
		break;

	case 1:
		$errorLevel = E_ERROR | E_PARSE;
		break;

	case 0:
	default:
		$errorLevel = 0;
		break;

}

if(STRICT)
{
	$errorLevel = $errorLevel | E_STRICT;
}
error_reporting($errorLevel);

require('system/classes/exceptions.class.php');
require('system/classes/config.class.php');
require('system/library/IniFile.class.php');
require('system/classes/displaymaker.class.php');
require('system/classes/database.class.php');
require('system/functions/general.functions.php');
require('system/engines/Engine.class.php');
require('system/classes/Password.class.php');
require('system/classes/User.class.php');
require('system/interfaces/ActionInterface.interface.php');
require('system/classes/Permissions.class.php');
require('system/classes/Page.class.php');
require('system/abstracts/ModuleBase.abstract.php');
require('system/abstracts/Plugin.abstract.php');
require('system/abstracts/action.class.php');
require('system/classes/Site.class.php');

require('system/classes/modelSupport/actions/Base.class.php');
require('system/classes/modelSupport/actions/Delete.class.php');
require('system/classes/modelSupport/actions/Add.class.php');
require('system/classes/modelSupport/actions/Edit.class.php');
require('system/classes/modelSupport/actions/Read.class.php');
require('system/interfaces/Model.interface.php');
require('system/abstracts/Model.class.php');


require('system/classes/modelSupport/Converters/Array.class.php');
require('system/classes/modelSupport/Converters/Html.class.php');




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
	}

	// system timezone, defaulting to UTC
	$timezone = (isset($config['system']['timezone'])) ? $config['system']['timezone'] : 'UTC';
	date_default_timezone_set($timezone);

	$request = new $requestWrapperName();
	$request->main();

}catch (Exception $e){
	echo 'An uncaught error occured.';
}

?>