<?php

class PdoManager
{

	/**
	 * This stores connections so that they can be reused without being reopened.
	 *
	 * @var array
	 */
	static private $dbConnections = array();

	/**
	 * This is the configuration file which stores all of the database settings
	 *
	 * @var ConfigFile
	 */
	static private $iniFile;

	/**
	 * An array of all allowed drivers, with the key being the driver type.
	 *
	 * @var array
	 */
	static protected $availableDrivers = array();

	/**
	 * This function created a PDO connection using the information stored in the database configuration file. This
	 * connection is stored for reuse by default.
	 *
	 * @param string $name
	 * @return PDO
	 */
	static function getStoredConnection($name, $store = true)
	{
		if($store && isset(self::$dbConnections[$name]))
			return self::$dbConnections[$name];

		$settings = self::getDatabaseSettings($name);

		if(!isset($settings['type']))
			throw new PdoManagerError('Settings file does not contain the database type.');

		$type = $settings['type'];
		unset($settings['type']);

		if(!($connection = self::getConnection($type, $settings)))
			throw new PdoManagerError('Unable to connect to database ' . $name . ' of type ' . $type);

		if($store)
			self::$dbConnections[$name] = $connection;

		return $connection;
	}


	/**
	 * This function is used to directly create a PDO connection.
	 *
	 * @param string $type
	 * @param array $options
	 * @return PDO
	 */
	static protected function getConnection($type, array $options)
	{
		if(!isset(self::$availableDrivers))
		{
			$availableDrivers = PDO::getAvailableDrivers();
			self::$availableDrivers = array_flip($availableDrivers);
		}

		if(!isset(self::$availableDrivers[$type]))
			throw new PdoError('Unable to load database due to missing driver: ' . $type);

		switch($type)
		{
			case 'sqlite2':
				$dsn = 'sqlite2';

			case 'sqlite':

				if(!isset($dsn))
					$dsn = 'sqlite:';

				if(isset($options['path']))
				{
					$dsn .= $options['path'];
				}elseif($options['memory']){
					$dsn .= ':memory:';
				}else{
					throw new PdoError('Unable to load sqlite database due to missing settings');
				}

				$pdo = new PDO($dsn);
				return $pdo;

			case 'pgsql':

				$dsn = 'pgsql:';
				$dsn .= self::getOptionsAsString(array('host', 'port', 'dbname', 'user', 'password'), ' ');

				$pdo = new PDO($dsn);
				return $pdo;

			case 'mysql':

				$dsn = 'mysql:';
				$dsn .= self::getOptionsAsString(array('host', 'port', 'dbname', 'unix_socket'), ';');

				if(!isset($options['username']))
					$options['username'] = null;

				if(!isset($options['password']))
					$options['password'] = null;

				$pdo = new PDO($dsn, $options['username'], $options['password']);
				return $pdo;

			default:
				throw new PdoError('Unable to load database due to unknown driver: ' . $type);
		}
	}

	/**
	 * Returns the stored options for the specified database as an array.
	 *
	 * @param string $name
	 * @return array
	 */
	static public function getDatabaseSettings($name)
	{
		if(!self::$iniFile)
		{
			$config = Config::getInstance();

			if(!isset($config['path']['config']) && defined('INSTALLMODE') && INSTALLMODE == true)
				return false;

			$path_to_dbfile = $config['path']['config'] . 'databases.php';
			$iniFile = new ConfigFile($path_to_dbfile);
			self::$iniFile = $iniFile;
		}

		return self::$iniFile->getArray($name);
	}

	/**
	 * When passed a name this function closes that specific connection, otherwise it clears out all connections.
	 *
	 * @param string $name
	 */
	static public function closeConnection($name = null)
	{
		if(isset($name))
		{
			if(isset(self::$dbConnections[$name]))
				unset(self::$dbConnections[$name]);
		}else{
			foreach(self::$dbConnections as $index => $connection)
				self::$dbConnections[$index] = null;

			$connection = null;
			self::$dbConnections = null;
			self::$dbConnections = array();
		}

	}

	static protected function getOptionsAsString(array $options, $optionDelimiter, $valueDelimiter = '=')
	{
		foreach($options as $option)
			if(isset($options['$option']))
				$dsnOptions[] = $option . '=' . $options[$option];

		return implode($optionDelimiter, $dsnOptions);
	}
}

class PdoError extends CoreError {}
?>