<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Caching
 */

if(!class_exists('cacheHandlerSqlite', false))
	include 'Sqlite.class.php';

/**
 * This class is used by the Cache class for persistent storage of cached objects using an sqlite file.
 *
 * @package System
 * @subpackage Caching
 */
class cacheHandlerSqliteOneFile extends cacheHandlerSqlite
{


	/**
	 * This is a stored sqlObject using the cache database. This way each cache call does not need to open the handler
	 * again, saving a bit of overhead.
	 *
	 * @var SQLiteDatabase
	 */
	static protected $sqlObject = false;

	/**
	 * This function takes in a key array, turns it into an sql key, and deletes all objects in the database whose
	 * keys begin with this key. If the argument passed is null the entire cache directory is deleted, or if it is a
	 * single word key the appropriste sqlite database is removed.
	 *
	 * @param null|array $key
	 */
	static function clear($key = null)
	{
		if(is_null($key) || (is_array($key) && count($key) == 0))
		{
			$config = Config::getInstance();
			deltree($config['path']['temp'] . 'cache');
			self::$sqlObject = false;
		}else{
			$key = self::makeSqlKey($key) . '%';
			$sqlResource = self::getSqliteHandler($key[0]);
			$query = $sqlResource->queryExec("DELETE FROM cacheStore WHERE key LIKE '{$key}'");
		}
	}

	/**
	 * This function is used to retrieve an SQLiteDatabase object. If the requested section does not exist, it creates
	 * and and sets up the structure.
	 *
	 * @param string
	 * @return bool
	 */
	static function getSqliteHandler($name)
	{
		try{
			if(!isset(self::$sqlObject) || get_class(self::$sqlObject) != 'SQLiteDatabase')
			{
				$config = Config::getInstance();
				$filePath = $config['path']['temp'] . 'cache/cache.sqlite';

				$isSetup = file_exists($filePath);

				if(!file_exists(dirname($filePath)))
				{
					if(!mkdir(dirname($filePath), 0700, true))
						return false;
				}

				$db = new SQLiteDatabase($filePath, '0666', $errorMessage);

				if(!$db)
					throw new CacheSqliteWarning('Unable to open SQLite Database: '. $errorMessage);

				if(!$isSetup)
				{
					$db->queryExec('
					CREATE TABLE cacheStore (
						key TEXT UNIQUE ON CONFLICT REPLACE,
						expires FLOAT,
						data BLOB
					);
					CREATE INDEX keyIndex ON cacheStore (key);');

				}

				$db->busyTimeout(self::$busyTimeout);
				self::$sqlObject = $db;
			}

		}catch(Exception $e){
			return false;
		}
		return self::$sqlObject;
	}
}

?>