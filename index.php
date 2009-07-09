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
	define('START_PROCESS_TIME', $startdat['ru_utime.tv_usec']);
	unset($startdat);
}

// Error Handling Setup

switch(DEBUG)
{
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
		$errorLevel = 0;
		break;

	default:
	case 4:
		$errorLevel = E_ALL;
		break;
}

if(STRICT)
	$errorLevel = $errorLevel | E_STRICT;

error_reporting($errorLevel);
unset($errorLevel);
if(ini_get('register_globals'))
{
	$names = array_merge(array_keys($_GET),
							array_keys($_POST),
							array_keys($_COOKIE),
							array_keys($_SERVER),
							array_keys($_ENV));
	foreach($names as $name)
		if(isset($name))
			unset($name);

	unset($names);
	unset($name);
}

require('system/classes/Exceptions.class.php');
require('system/classes/Config.class.php');
require('system/library/IniFile.class.php');
require('system/classes/DisplayMaker.class.php');
require('system/classes/Database.class.php');
require('system/functions/general.functions.php');
require('system/classes/Password.class.php');
require('system/classes/User.class.php');
require('system/interfaces/ActionInterface.interface.php');
require('system/classes/Permissions.class.php');
require('system/classes/Page.class.php');

require('system/abstracts/action.class.php');
require('system/classes/Site.class.php');


require('system/classes/modelSupport/actions/Base.class.php');
require('system/classes/modelSupport/actions/Add.class.php');
require('system/classes/modelSupport/actions/Read.class.php');
require('system/classes/modelSupport/actions/Edit.class.php');
require('system/classes/modelSupport/actions/Index.class.php');


require('system/classes/modelSupport/actions/LocationBased/Base.class.php');
require('system/classes/modelSupport/actions/LocationBased/Add.class.php');
require('system/classes/modelSupport/actions/LocationBased/Edit.class.php');
require('system/classes/modelSupport/actions/LocationBased/Read.class.php');
require('system/classes/modelSupport/actions/LocationBased/Index.class.php');
require('system/classes/modelSupport/actions/LocationBased/Delete.class.php');

require('system/classes/modelSupport/Listings/ModelListing.class.php');
require('system/classes/modelSupport/Listings/LocationListing.class.php');


require('system/interfaces/Model.interface.php');
require('system/abstracts/Model.class.php');
require('system/abstracts/LocationModel.class.php');

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