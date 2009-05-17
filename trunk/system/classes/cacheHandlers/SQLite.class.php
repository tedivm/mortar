<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Caching
 */

/**
 * This class is used by the Cache class for persistent storage of cached objects using an sqlite file.
 *
 * @package System
 * @subpackage Caching
 */
class cacheHandlerSqlite implements cacheHandler
{
	/**
	 * This is a string that represents the item being managed.
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * This is the data being stored
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * This is a stored sqlObject using the cache database. This way each cache call does not need to open the handler
	 * again, saving a bit of overhead.
	 *
	 * @var SQLiteDatabase
	 */
	static protected $sqlObject = false;

	/**
	 * This is the time, in miliseconds, that the cacheHandler should wait for results. If this number is too high it
	 * makes it possible, in some situations, for the cacheHandler to actually slow down, instead of speed up, a request
	 *
	 * @var int
	 */
	static public $busyTimeout = 500;

	/**
	 * This takes in a key (array) and turns it into the sql key. It also sets up the SQLiteDatabase object, returning
	 * false on failure.
	 *
	 * @param array $key
	 * @return bool
	 */
	public function setup($key)
	{
		$this->key = self::makeSqlKey($key);

		if(get_class(self::$sqlObject) == 'SQLiteDatabase')
			return true;

		return (self::setSqliteHandler());
	}

	/**
	 * This returns the data from the SQLiteDatabase
	 *
	 * @return array
	 */
	public function getData()
	{

		$query = self::$sqlObject->query("SELECT * FROM cacheStore WHERE key LIKE '{$this->key}'");

		if($resultArray = $query->fetch(SQLITE_ASSOC))
		{
			$results = array('expiration' => $resultArray['expires'], 'data' => unserialize($resultArray['data']));
		}else{
			$results = false;
		}

		return $results;
	}

	/**
	 * This stores the data array into the SQLiteDatabase.
	 *
	 * @param array $data
	 * @param int $expiration
	 */
	public function storeData($data, $expiration)
	{
		$data = sqlite_escape_string(serialize($data));

		$query = self::$sqlObject->query("INSERT INTO cacheStore (key, expires, data)
											VALUES ('{$this->key}', '{$expiration}', '{$data}')");
	}

	/**
	 * This function takes in a key array, turns it into an sql key, and deletes all objects in the database whose
	 * keys begin with this key. If the argument passed is null the entire sqlite file is deleted.
	 *
	 * @param null|array $key
	 */
	static function clear($key = null)
	{
		if(is_null($key) || (is_array($key) && count($key) == 0))
		{
			$info = InfoRegistry::getInstance();
			$filePath = $info->Configuration['path']['temp'] . 'cacheDatabase.sqlite';
			unlink($filePath);
		}else{
			if(!self::$sqlObject)
			{
				self::setSqliteHandler();
			}
			$key = self::makeSqlKey($key) . '%';
			$query = self::$sqlObject->queryExec("DELETE FROM cacheStore WHERE key LIKE '{$key}'");
		}
	}

	/**
	 * This function is used to retrieve the SQLiteDatabase object. If one is not set, it opens one. If the created
	 * object is a new database, it creates the structure.
	 *
	 * @return bool
	 */
	static function setSqliteHandler()
	{
		try{
			if(get_class(self::$sqlObject) != 'SQLiteDatabase')
			{
				$info = InfoRegistry::getInstance();
				$filePath = $info->Configuration['path']['temp'] . 'cacheDatabase.sqlite';

				$isSetup = file_exists($filePath);

				$db = new SQLiteDatabase($filePath, '0666', $errorMessage);

				if(!$db)
					throw new BentoWarning('Unable to open SQLite Database: '. $errorMessage);

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

		return true;
	}

	/**
	 * This function takes an array of strings and turns it into the sqlKey. It does this by iterating through the
	 * array, running the string through sqlite_escape_string() and then combining that string to the keystring with a
	 * delimiter.
	 *
	 * @param array $key
	 * @return string
	 */
	static function makeSqlKey($key)
	{
		$pathPiece = '';
		foreach($key as $rawPathPiece)
		{
			$pathPiece .= sqlite_escape_string($rawPathPiece) . ':::';
		}

		return $pathPiece;
	}

	/**
	 * This function checks to see if it is possble to enable this handler. It does so here by making sure the
	 * SQLiteDatabase class exists.
	 *
	 * @return bool
	 */
	static function canEnable()
	{
		return class_exists('SQLiteDatabase', false);
	}
}

?>