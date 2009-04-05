<?php
/*

very simple cache class.

$cache = new Cache(unique-name);

if(!$stuff = $cache->get_data())
{
	// Do the stuff you were hoping to skip, that dumps things into $data

	$config->store_data($stuff);
}

echo $stuff;

*/



class Cache
{
	public $name;
	public $path;
	public $cache_time = 1800; //seconds

	public $cacheReturned = false;
	protected $cache_enabled = true;

	protected $key;
	protected $keyString;

	protected $handler;
	protected static $handlerClass = '';
	protected static $handlers = array('FileSystem' => 'cacheHandlerFilesystem',
										'SQLite' => 'cacheHandlerSqlite');

	static $runtimeDisable = false;
	static $cacheCalls = 0;
	static $cacheReturns = 0;
	static $memStore = array();

	static private $queryRecord;// = array();

	public function __construct()
	{
		self::$cacheCalls++;
		if((defined('DISABLECACHE') && DISABLECACHE) || self::$runtimeDisable)
		{
			$this->cache_enabled = false;
			return;
		}

		try {

			if((defined('DISABLECACHE') && DISABLECACHE) || self::$runtimeDisable)
				throw new BentoNotice('Cache disabled.');

			if(func_num_args() == 0)
				throw new BentoError('no cache argument');

			if(self::$handlerClass == '')
			{
				$config = Config::getInstance();
				self::$handlerClass = (isset(self::$handlers[$config['system']['cache']]))
										? self::$handlers[$config['system']['cache']]
										: self::$handlers['FileSystem'];
			}

			$key = func_get_args();

			if(count($key) == 1 && is_array($key[0]))
				$key = $key[0];

			$key = (is_array($key[0])) ? $key[0] : $key;

			$this->key =array_map('strtolower', $key);

			$this->keyString = implode(':::', $this->key);
			$this->handler = new self::$handlerClass();
			if(!$this->handler->setup($this->key))
				throw new BentoError('Unable to setup cache handler.');

			$this->cache_enabled = true;


			if(BENCHMARK)
			{
				$keyString = implode('/', $this->key);

				if(isset(self::$queryRecord[$keyString]))
				{
					self::$queryRecord[$keyString]++;
				}else{
					self::$queryRecord[$keyString] = 1;
				}
			}

		}catch (Exception $e){
			$this->cache_enabled = false;
		}

	}

	static public function getCalls()
	{
		return self::$queryRecord;
	}

	static public function clear()
	{
		if((defined('DISABLECACHE') && DISABLECACHE) || self::$runtimeDisable)
			return true;

		if(self::$handlerClass == '')
		{
			$handlers = self::getHandlers();
			$config = Config::getInstance();
			self::$handlerClass = (isset($handlers[$config['cacheType']])) ? $handlers[$config['cacheType']]
																			: $handlers['FileSystem'];
		}

		if(self::$handlerClass != '')
		{
			$args = func_get_args();
			return staticFunctionHack(self::$handlerClass, 'clear', $args);
		}
	}

	public function getData()
	{
		if(!$this->cache_enabled)
			return false;

		if(isset(self::$memStore[$this->keyString]) && is_array(self::$memStore[$this->keyString]))
		{
			$record = self::$memStore[$this->keyString];
		}else{
			$record = $this->handler->getData();
			self::$memStore[$this->keyString] = $record;
		}

		if($record['expiration'] - START_TIME < 0)
		{
			return false;
		}
		$this->cacheReturned = true;

		self::$cacheReturns++;
		return $record['data']['return'];
	}

	public function storeData($data)
	{
		if(!$this->cache_enabled)
			return;

		$store['return'] = $data;
		$store['createdOn'] = START_TIME;

		try{
			$random = $this->cache_time * .1 ;
			$expiration = (microtime(true) + ($this->cache_time + rand(-1 * $random , $random)));

			self::$memStore[$this->keyString] = array('expiration' => $expiration, 'data' => $store);

			$this->handler->storeData($store, $expiration);
		}catch(Exception $e){

		}
	}

	public function extendCache()
	{
		if(!$this->cache_enabled)
			return;

		return $this->storeData(self::$memStore[$this->keyString]['data']['return']);
	}

	static function getHandlers()
	{
		foreach(self::$handlers as $name => $class)
		{
			if(staticFunctionHack($class, 'canEnable'))
				$availableHandlers[$name] = $class;
		}

		return $availableHandlers;
	}


	// alias functions
	public function get_data()
	{
		return $this->getData();
	}

	public function store_data($data)
	{
		return $this->storeData($data);
	}

}

interface cacheHandler
{
	public function setup($key); // return boolean

	public function getData();

	public function storeData($data, $expiration);

	static function clear($key = '');

}

class cacheHandlerFilesystem implements cacheHandler
{
	protected $path;
	protected $data;
	protected $cache_enabled = false;
	public $cacheReturned = false;
	public $cache_time = 30;

	protected static $memStore = array();

	protected static $cachePath;

	public function setup($key)
	{
		$this->path = self::makePath($key);
		return ($this->path !== false);
	}

	public function getData()
	{
		if(file_exists($this->path))
		{
			$file = fopen($this->path, 'r');
			$filesize = filesize($this->path);
			if(flock($file, LOCK_SH | LOCK_NB))
			{
				$data = fread($file, $filesize);
				flock($file, LOCK_UN);
				$store = unserialize($data);
				return $store;

			}else{
				$this->cache_enabled = false;
				// the only way to get here is if there is a write lock already in place
				// so we disable caching to make sure this one doesn't attempt to write to the file
			}

		}
		return false;

	}

	public function storeData($data, $expiration)
	{
		if(!is_dir(dirname($this->path)))
		{
			if(!mkdir(dirname($this->path), 0755, true))
				return false;
		}

		$store['expiration'] = $expiration; // (microtime(true) + ($this->cache_time + rand(-1 * $random , $random)));
		$store['data'] = $data;


		$file = fopen($this->path, 'w+');
		if(flock($file, LOCK_EX))
		{
			if(!fwrite($file, serialize($store)))
			{

			}
			flock($file, LOCK_UN);
		}



	}

	static protected function makePath($key)
	{
		if(!isset(self::$cachePath))
		{
			$config = Config::getInstance();
			self::$cachePath = $config['path']['temp'] . 'cache/';
		}

		$path = self::$cachePath;

		// When I profiled this compared to the "implode" function, this was much faster
		// This is probably due to the small size of the arrays and the overhead from function calls
		$memkey = '';
		foreach($key as $group)
		{
			$memkey .= $group . '/' ;
		}

		if(isset(self::$memStore['keys'][$memkey]))
		{
			$path = self::$memStore['keys'][$memkey];
		}else{

			foreach($key as $index => $value)
			{
				$key[$index] = md5($value);
			}

			switch (count($key)) {
				case 0:
					return $path;
					break;

				case 1:
					$path .= $key[0] . '.php';//(ctype_alnum($key[0])) ? $key[0] : preg_replace('/[^a-zA-Z0-9]/u', '', $key[0]);
					break;

				default:
					$name = array_pop($key);
//					$path .= implode('/', $key);

					foreach($key as $group)
					{
						$path .= ($group[0]) ? $group . '/' : '';
					}



					$path .= $name . '.php';
					break;
			}

			self::$memStore['keys'][$memkey] = $path;

		}



		return $path;
	}

	static public function clear($key = '')
	{

		$path = self::makePath($key);

		if($path)
		{

			if(is_file($path))
			{

				unlink($path);
			}

			if(strpos($path, '.php') !== false)
			{
				$dir = dirname($path);

			}elseif(is_dir($path)){
				$dir = $path;
			}

			if($dir)
			{
				deltree($path);
			}

		}else{
			return false;
		}

		return true;
	}

	static function canEnable()
	{
		return true;
	}

}

class cacheHandlerSqlite implements cacheHandler
{
	protected $key;
	protected $data;

	static protected $sqlObject = false;


	public function setup($key)
	{
		$this->key = self::makeSqlKey($key);

		if(get_class(self::$sqlObject) == 'SQLiteDatabase')
			return true;

		return (self::setSqliteHandler());
	}

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

	public function storeData($data, $expiration)
	{
		$data = sqlite_escape_string(serialize($data));

		$query = self::$sqlObject->query("INSERT INTO cacheStore (key, expires, data)
											VALUES ('{$this->key}', '{$expiration}', '{$data}')");
	}

	static function clear($key = null)
	{
		if(is_null($key))
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

				self::$sqlObject = $db;
			}

		}catch(Exception $e){
			return false;
		}

		return true;
	}

	static function makeSqlKey($key)
	{
		foreach($key as $rawPathPiece)
		{
			$pathPiece .= sqlite_escape_string($rawPathPiece) . ':::';
		}

		return $pathPiece;
	}

	static function canEnable()
	{
		return class_exists('SQLiteDatabase', false);
	}
}
?>