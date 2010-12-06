<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

class BootStrapper
{
	static function main()
	{
		self::clearGlobals();
		self::setEnvironmentalConstants();
		self::loadClasses();
		self::setErrorLevels();
		self::run();
	}

	static protected function setEnvironmentalConstants()
	{
		define('START_TIME', microtime(true));

		if(file_exists('data/configuration/system.php'))
			include('data/configuration/system.php');

		$runtimeProfile = defined('RUNTIME_PROFILE') ? strtolower(RUNTIME_PROFILE) : 'runtime';

		require('system/data/profiles/' . $runtimeProfile . '.php');
		require('system/data/Main.constants.php');


		if(BENCHMARK && function_exists('getrusage'))
		{
			$startdat = getrusage();
			$startProcTime = $startdat["ru_utime.tv_usec"];
			define('START_PROCESS_TIME', $startdat['ru_utime.tv_usec']);
		}

		if(!defined('PROGRAM_NAME'))
			BootStrapper::define('PROGRAM_NAME', 'Mortar');

		BootStrapper::define('BASE_PATH', dirname(__FILE__) . '/');
		$pathArray = explode('/', __FILE__);
		$dispatcher = array_pop($pathArray);
		BootStrapper::define('DISPATCHER', $dispatcher);

		if(isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['HTTP_HOST']))
		{
			$base = $_SERVER['HTTP_HOST']
					. substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['SCRIPT_NAME']) - strlen(DISPATCHER));
			BootStrapper::define('BASE_URL', $base);
		}
	}

	static function define($name, $value)
	{
		if(!defined($name))
			define($name, $value);
	}

	static function clearGlobals()
	{
		// if register globals is enabled kill it with fire.
		$registerGlobals = ini_get('register_globals');
		if($registerGlobals && $registerGlobals != 'off')
		{
			$names = array_merge(array_keys($_GET),
									array_keys($_POST),
									array_keys($_REQUEST),
									array_keys($_COOKIE),
									array_keys($_SERVER),
									array_keys($_ENV));
			foreach($names as $name)
				if(isset($GLOBALS[$name]))
					unset($GLOBALS[$name]);

			foreach($_FILES as $fileInput => $fileAttributes)
				foreach($fileAttributes as $attributeName => $attibuteValue)
				{
					$variableName = $fileInput . '_' . $attributeName;
					if(isset($GLOBALS[$variableName]))
						unset($GLOBALS[$variableName]);
				}
		}
	}

	static protected function setErrorLevels()
	{
		if(defined('STDIN'))
			define('EXCEPTION_OUTPUT', 'Text');

		// Error Handling Setup

		switch(DEBUG)
		{
			case 3:
				$errorLevel = E_ALL;
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
	}

	static protected function loadClasses()
	{
		require('system/classes/Exceptions.class.php');
		require('system/classes/DepreciatedExceptions.class.php');
		require('system/classes/Config.class.php');
		require('system/library/IniFile.class.php');
		require('system/library/ConfigFile.class.php');
		require('system/classes/MySql.class.php');
		require('system/classes/Sqlite.class.php');
		require('system/functions/general.functions.php');
		require('system/classes/Permissions.class.php');
		require('system/thirdparty/Stash/Autoloader.class.php');
		require('system/classes/CacheControl.class.php');
		require('system/classes/PackageList.class.php');
		require('system/classes/PackageInfo.class.php');
		require('system/classes/Version.class.php');
		require('system/classes/AutoLoader.class.php');
	}

	static protected function run()
	{
		try{

			$config = Config::getInstance();

			$requestWrapperName = 'RequestWrapper';

			// If an error occured we may be looking at a pre-installation setup
			if($config->error)
			{
				// disable cache, since we can't load the settings for it anyways
				CacheControl::disableCache();

				// If the install class file is not, we shouldn't attempt an install
				if(!file_exists($config['path']['modules'] . 'Installer/actions/Install.class.php'))
				{
					define('INSTALLMODE', false);
					throw new CoreError('Unable to load configuration file.');
				}else{
					// there is no config and the installer is present so we install
					define('INSTALLMODE', true);
					$requestWrapperName = 'RequestWrapperInstaller';
					require('system/classes/RequestWrapperInstaller.class.php');
				}

			}else{
				// config loaded, so lets set install mode to false
				define('INSTALLMODE', false);
			}

			// system timezone, defaulting to UTC
			$timezone = (isset($config['system']['timezone'])) ? $config['system']['timezone'] : 'UTC';
			date_default_timezone_set($timezone);

			$request = new $requestWrapperName();
			$request->main();

		}catch (Exception $e){
			echo 'An uncaught error occured. This really should not happen, so if you are the sysadmin or webmaster please file a bug report.' . PHP_EOL;
		}
	}
}

BootStrapper::main();
exit(); // some hosts apparently append javascript to php requests, and this prevents that idiocy
?>