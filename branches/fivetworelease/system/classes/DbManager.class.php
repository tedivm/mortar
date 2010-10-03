<?php

class DbManager
{
	/**
	 * An array of all allowed drivers and their DSN handlers
	 *
	 * @var array
	 */
	static protected $availableDrivers = array();

	static protected $standardHandlers = array('sqlite2' => 'DbDriverSqlite2',
									   'sqlite' => 'DbDriverSqlite',
									   'pgsql' => 'DbDriverPgSql',
									   'mysql' => 'DbDriverMySql');

	/**
	 * This stores connections so that they can be reused without being reopened.
	 *
	 * @var array
	 */
	static protected $dbConnections = array();

	static public function getConnection($name, $options = array())
	{
		$store = ($options['store'] !== false);

		if(($options['store'] !== false) && isset(self::$dbConnections[$name]))
			return self::$dbConnections[$name];

		if(isset($options['connectionParameters']))
		{
			$settings = $options['connectionParameters'];
		}else{
			if(isset($options['settingsFile']))
			{
				$path = $options['settingsFile'];
			}else{
				$config = Config::getInstance();
				if(!isset($config['path']['config']) && defined('INSTALLMODE') && INSTALLMODE == true)
					return false;

				$path = $config['path']['config'] . 'databases.php';
			}
			$settings = self::getDatabaseSettings($name, $path);
		}

		if(!isset($settings['type']))
			throw new DbError('Unable to open connection to ' . $name . ' without driver type.');

		$type = $settings['type']; unset($settings['type']);

		$settings['class'] = isset($options['class']) ? $options['class'] : 'MortarPdo';
		if($pdoObject = self::openConnection($type, $settings))
		{
			$pdoObject->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

			// set the Statement class to use- if passed the PDOStatement class, PDO's default, then don't set anything
			$stmtClass = isset($options['statementClass']) ? $options['statementClass'] : 'MortarPdoStatement';
			if($stmtClass !== 'PDOStatement')
			{
				$stmtClassArgs = isset($options['statementClassArgs']) ? $options['statementClassArgs'] : array();
				$pdoObject->setAttribute(PDO::ATTR_STATEMENT_CLASS, array($stmtClass, $stmtClassArgs));
			}

			// store the connection for later
			if($store)
				self::$dbConnections[$name] = $pdoObject;

			return $pdoObject;
		}else{
			return false;
		}


	}

	static public function openConnection($type, $options = array())
	{
		$driverList = self::getAvailableDrivers();

		if(!isset($driverList[$type]))
			return false;

		$driver = new $driverList[$type]();

		if(!$driver->validate($options))
			throw new DbError('Unable to open connection to ' . $type . ' type database with passed options.');

		try{
			$pdo = $driver->getPdo();
		}catch(Exception $e){
			throw new DbError($e->getMessage(), (int) $e->getCode());
		}

		return $pdo;

	}

	static public function registerHandler($name, $class)
	{
		self::$availableHandlers[$name] = $class;
	}

	static public function getAvailableDrivers()
	{
		if(!isset(self::$availableDrivers))
		{
			$availableDrivers = MortarPdo::getAvailableDrivers();
			$drivers = array_flip($availableDrivers);

			foreach($availableDrivers as $driver)
				if(isset(self::$handlers[$driver]))
					self::registerHandler($driver, self::$handlers[$driver]);
		}

		return self::$availableDrivers;
	}

	/**
	 * Returns the stored options for the specified database as an array.
	 *
	 * @param string $name
	 * @param string $path
	 * @return array
	 */
	static public function getDatabaseSettings($name, $path)
	{
		if(!isset(self::$iniFile[$path]))
		{
			$class = (substr($path, -3) == 'ini') ? 'IniFile' : 'ConfigFile';
			$iniFile = new $class($path);
			self::$iniFile[$path] = $iniFile;
		}

		return self::$iniFile[$path]->getArray($name);
	}

	static public function getOptionsAsString(array $options, $expectedOptions, $optionDelimiter)
	{
		foreach($expectedOptions as $option)
			if(isset($options[$option]))
				$dsnOptions[] = $option . '=' . $options[$option];

		return implode($optionDelimiter, $dsnOptions);
	}

}

class MortarPdo extends PDO
{

}

class MortarPdoStatement extends PDOStatement
{

}


class DbError extends CoreError {}
?>