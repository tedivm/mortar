<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Caching
 */

/**
 * This class is used by the Cache class for persistent storage of cached objects using multiple sqlite files.
 *
 * @package System
 * @subpackage Caching
 */
class StashSqlite implements StashHandler
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
	 * This is the name of the current section, identified by being the first string in the key.
	 *
	 * @var string
	 */
	protected $section;

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
		$this->section = $key[0];
		$this->key = self::makeSqlKey($key);
		$sqlResource = staticFunctionHack(get_class($this), 'getSqliteHandler', $this->section);
		return (get_class($sqlResource) == 'SQLiteDatabase');
	}

	/**
	 * This returns the data from the SQLiteDatabase
	 *
	 * @return array
	 */
	public function getData()
	{
		//$sqlResource = self::getSqliteHandler($this->section);
		$sqlResource = staticFunctionHack(get_class($this), 'getSqliteHandler', $this->section);
		$query = $sqlResource->query("SELECT * FROM cacheStore WHERE key LIKE '{$this->key}'");

		if($resultArray = $query->fetch(SQLITE_ASSOC))
		{
			$returnData = Cache::decode($resultArray['data'], $resultArray['encoding']);
			$results = array('expiration' => $resultArray['expires'], 'data' => $returnData);
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
		$encoding = Cache::encoding($data);
		$data = Cache::encode($data);
		$data = sqlite_escape_string($data);
		$sqlResource = staticFunctionHack(get_class($this), 'getSqliteHandler', $this->section);

		$resetBusy = false;
		$contentLength = strlen($data);
		if($contentLength > 100000)
		{
			$resetBusy = true;
			$sqlResource->busyTimeout(self::$busyTimeout * (ceil($contentLength/100000))); // half a second per 100k
		}

		$query = $sqlResource->query("INSERT INTO cacheStore (key, expires, data, encoding)
											VALUES ('{$this->key}', '{$expiration}', '{$data}', '{$encoding}')");

		if($resetBusy)
			$sqlResource->busyTimeout(self::$busyTimeout);
	}

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
			SqliteConnection::clear();
		}elseif(is_array($key) && count($key) == 1){

			$config = Config::getInstance();
			$name = array_shift($key);

			deltree($config['path']['temp'] . 'cache/' . $name . '.sqlite');
			self::$sqlObject[$name] = null;
			SqliteConnection::clear();
		}else{
			$key = self::makeSqlKey($key) . '%';
			$sqlResource = staticFunctionHack(get_class($this), 'getSqliteHandler', $this->section);
			$query = $sqlResource->queryExec("DELETE FROM cacheStore WHERE key LIKE '{$key}'");
		}
	}

	/**
	 * Removes all stale data from each of the sqlite databases that make up the cache.
	 *
	 * @return bool
	 */
	static function purge()
	{
		$config = Config::getInstance();
		$filePath = $config['path']['temp'] . 'cache/';

		$databases = glob($filePath . '*.sqlite');
		foreach($databases as $database)
		{
			$tmpArray = explode('/', $filename);
			$tmpArray = array_pop($tmpArray);
			$tmpArray = explode('.', $tmpArray);
			$cacheName = array_shift($tmpArray);

			$handler = self::getSqliteHandler($cacheName);
			$handler->query('DELETE FROM cacheStore WHERE expires < ' . microtime(true));
		}
		return true;
	}

	/**
	 * This function is used to retrieve an SQLiteDatabase object. If the requested section does not exist, it creates
	 * and and sets up the structure.
	 *
	 * @param string
	 * @return SQLiteDatabase
	 */
	static function getSqliteHandler($name)
	{
		try {
			if(isset(self::$sqlObject[$name]) && get_class(self::$sqlObject[$name]) == 'SQLiteDatabase')
				return self::$sqlObject[$name];

			$config = Config::getInstance();
			$filePath = $config['path']['temp'] . 'cache/';

			if(!file_exists($filePath))
				mkdir($filePath);

			if(!$db = SqliteConnection::getDatabase($name, $filePath))
			{
				$creationResults = SqliteConnection::createDatabase($name,'
						CREATE TABLE cacheStore (
							key TEXT UNIQUE ON CONFLICT REPLACE,
							expires FLOAT,
							encoding TEXT,
							data BLOB
						);
						CREATE INDEX keyIndex ON cacheStore (key);', false, $filePath);

				if(!($creationResults && $db = SqliteConnection::getDatabase($name, $filePath)))
					return false;
			}

			$db->busyTimeout(self::$busyTimeout);
			self::$sqlObject[$name] = $db;
			return $db;

		}catch(Exception $e){
			return false;
		}
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

class CacheSqliteWarning extends CoreWarning {}
?>