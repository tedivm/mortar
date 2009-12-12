<?php

class SqliteConnection
{

	static $sqliteConnections = array();

	static function getDatabase($name, $path = null)
	{
		try{
			if(!isset(self::$sqliteConnections[$name]) || get_class(self::$sqliteConnections[$name]) != 'SQLiteDatabase')
			{
				if(!isset($path))
				{
					$config = Config::getInstance();
					$filePath = $config['path']['temp'];
				}else{
					$filePath = $path;
				}

				$filePath .= $name . '.sqlite';

				$isSetup = file_exists($filePath);

				if(!file_exists($filePath))
					return false;

				if(!$db = new SQLiteDatabase($filePath, '0666', $errorMessage))
					throw new CacheSqliteWarning('Unable to open SQLite Database: '. $errorMessage);

				self::$sqliteConnections[$name] = $db;
			}

			return self::$sqliteConnections[$name];

		}catch(Exception $e){
			return false;
		}
	}

	static function createDatabase($name, $creationSql, $force = false, $path = null)
	{
		try{
			if(!isset($path))
			{
				$config = Config::getInstance();
				$filePath = $config['path']['temp'] . '/';
			}else{
				$filePath = $path;
			}

			if(!file_exists($filePath))
				return false;

			$filePath .= $name . '.sqlite';

			if(file_exists($filePath))
			{
				if(!$force)
					return false;

				if(isset(self::$sqliteConnections[$name]))
					unset(self::$$sqliteConnections[$name]);

				unlink($filePath);
			}

			if(!$db = new SQLiteDatabase($filePath, '0666', $errorMessage))
				throw new CacheSqliteWarning('Unable to open SQLite Database: '. $errorMessage);

			if(!$db->queryExec($creationSql, $errorMessage))
			{
				unlink($filePath);
				throw new CacheSqliteWarning('Unable to set SQLite: structure: '. $errorMessage);
			}

			self::$sqliteConnections[$name] = $db;
			return true;

		}catch(Exception $e){
			return false;
		}
	}

	static function clear()
	{
		self::$sqliteConnections = array();
	}

}

?>