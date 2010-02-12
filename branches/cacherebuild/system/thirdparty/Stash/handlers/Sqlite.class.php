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

	protected $responseCode = SQLITE_ASSOC;

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

	protected $creationSql = 'CREATE TABLE cacheStore (
							key TEXT UNIQUE ON CONFLICT REPLACE,
							expires FLOAT,
							encoding TEXT,
							data BLOB
						);
						CREATE INDEX keyIndex ON cacheStore (key);';

	/**
	 * This is the base path for the cache items to be saved in. This defaults to a directory in the tmp directory (as
	 * defined by the configuration) called 'stash_', which it will create if needed.
	 *
	 * @var string
	 */
	protected $cachePath;

	public function __construct($options = array())
	{
		if(isset($options['path']))
		{
			$this->cachePath = $options['path'];
			$lastChar = substr($this->cachePath, -1);

			if($lastChar != '/' && $lastChar != '\'')
				$this->cachePath .= '/';

		}else{
			$this->cachePath = Stash::getBaseDirectory($this);
		}
	}

	/**
	 * This returns the data from the SQLite database
	 *
	 * @return array
	 */
	public function getData($key)
	{
		$sqlKey = self::makeSqlKey($key);

		if(!($sqlResource = $this->getSqliteHandler($key[0])))
			return null;

		$query = $sqlResource->query("SELECT * FROM cacheStore WHERE key LIKE '{$sqlKey}'");

		if($query !== false && $resultArray = $query->fetch($this->responseCode))
		{
			$returnData = Stash::decode(base64_decode($resultArray['data']), $resultArray['encoding']);
			$results = array('expiration' => $resultArray['expires'], 'data' => $returnData);
		}else{
			$results = false;
		}

		return $results;
	}

	/**
	 * This stores the data array into the SQLite database.
	 *
	 * @param array $data
	 * @param int $expiration
	 */
	public function storeData($key, $data, $expiration)
	{
		$sqlKey = self::makeSqlKey($key);
		$encoding = Stash::encoding($data);
		$data = Stash::encode($data);
		$data = base64_encode($data);

		if(!($sqlResource = $this->getSqliteHandler($key[0])))
			return false;

		$resetBusy = false;
		$contentLength = strlen($data);
		if($contentLength > 100000)
		{
			$resetBusy = true;
			self::setTimeout($sqlResource, self::$busyTimeout * (ceil($contentLength/100000))); // .5s per 100k
		}

		$query = $sqlResource->query("INSERT INTO cacheStore (key, expires, data, encoding)
											VALUES ('{$sqlKey}', '{$expiration}', '{$data}', '{$encoding}')");

		if($resetBusy)
			self::setTimeout($sqlResource, self::$busyTimeout);
	}

	static function setTimeout($sqliteHandler, $milliseconds)
	{
		if($sqliteHandler instanceof PDO)
		{
			$timeout = ceil($milliseconds/1000);
			$sqliteHandler->setAttribute(PDO::ATTR_TIMEOUT, $timeout);
		}else{
			$sqliteHandler->busyTimeout($milliseconds);
		}
	}

	/**
	 * This function takes in a key array, turns it into an sql key, and deletes all objects in the database whose
	 * keys begin with this key. If the argument passed is null the entire cache directory is deleted, or if it is a
	 * single word key the appropriste sqlite database is removed.
	 *
	 * @param null|array $key
	 */
	public function clear($key = null)
	{
		if(is_null($key) || (is_array($key) && count($key) == 0))
		{
			Stash::deleteRecursive($this->cachePath);
			self::$sqlObject = false;
			Stash::$runtimeDisable = true;
		}elseif(is_array($key) && count($key) == 1){

			$name = array_shift($key);

			unlink($this->cachePath . $name . '.sqlite');
			self::$sqlObject[$name] = null;
		}else{
			$sqlKey = self::makeSqlKey($key);
			$sqlResource = $this->getSqliteHandler($key[0]);
			$query = $sqlResource->query("DELETE FROM cacheStore WHERE key LIKE '{$sqlKey}%'");
		}
	}

	/**
	 * Removes all stale data from each of the sqlite databases that make up the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		$filePath = $this->cachePath;

		$databases = glob($filePath . '*.sqlite');
		$expiration = microtime(true);
		foreach($databases as $database)
		{
			$tmpArray = explode('/', $filename);
			$tmpArray = array_pop($tmpArray);
			$tmpArray = explode('.', $tmpArray);
			$cacheName = array_shift($tmpArray);

			$handler = $this->getSqliteHandler($cacheName);
			$handler->query('DELETE FROM cacheStore WHERE expires < ' . $expiration);
		}
		return true;
	}

	/**
	 * This function is used to retrieve an SQLiteDatabase object. If the requested section does not exist, it creates
	 * and and sets up the structure.
	 *
	 * @param string
	 * @return SQLiteDatabase|SQLite3
	 */
	public function getSqliteHandler($name)
	{
		try {

			if(isset(self::$sqlObject[$name]) && is_object(self::$sqlObject[$name]))
					return self::$sqlObject[$name];

			if(!file_exists($this->cachePath))
				mkdir($this->cachePath, 0770, true);

			$path = $this->cachePath . $name . '.sqlite';

			$runInstall = !file_exists($path);

			try{
				$db = new PDO('sqlite:' . $path);
				$this->responseCode = PDO::FETCH_ASSOC;
			}catch(Exception $e){
				if(isset($db)) unset($db);
			}

			if(!isset($db))
			{
				if(!$db = new SQLiteDatabase($path, '0666', $errorMessage))
					throw new StashSqliteError('Unable to open SQLite Database: '. $errorMessage);
			}

			if($runInstall && !$db->query($this->creationSql))
			{
				unlink($path);
				throw new StashSqliteError('Unable to set SQLite: structure');
			}

			// prevent the cache from getting hungup waiting on a return
			self::setTimeout($db, self::$busyTimeout);

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
	 * SQLite3 or SQLiteDatabase class exists.
	 *
	 * @return bool
	 */
	static function canEnable()
	{
		return class_exists('PDO', false) || class_exists('SQLiteDatabase', false);
	}

}

class StashSqliteError extends StashError {}
?>