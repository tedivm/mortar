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
 * This class is used to cache data that has a high generation cost, such as template preprocessing or code that
 * requires a database query. This class can store any native php datatype, as long as it can be serialized (so
 * when creating classes that you wish to store instances of, remember the __sleep and __wake magic functions).
 *
 * @package System
 * @subpackage Caching
 * @todo Document the "Purge" functions
 */
class Stash
{
	/**
	 * This is how long, in seconds, the cache will last for
	 *
	 * @var int seconds
	 */
	public $cacheTime = 432000; // five days

	/**
	 * This is a flag to see if a valid response is returned.
	 *
	 * @var bool
	 */
	public $cacheReturned = false;

	/**
	 * If set to true, the system stores a copy of the current cache data (key, data and expiration) stored to a static
	 * variable. This allows future requests to that object to bypass retriving it from the cachehandler, but the trade
	 * off is that scripts use a bit more memory. For large pieces of data not likely to be called multiple times in a
	 * script (template data, for instance) this should be set to false.
	 *
	 * @var bool
	 */
	public $storeMemory = true;

	/**
	 * This is used internally to mark the class as disabled. This is effective only for the current request.
	 *
	 * @var bool
	 */
	protected $cache_enabled = true;

	/**
	 * This is the identifier for the item being cached. It is set by passing values to the constructor.
	 *
	 * @var array of strings
	 */
	protected $key;

	/**
	 * This is the key, but as a string instead of an array. This is primarily used as the index in various arrays.
	 *
	 * @var string
	 */
	protected $keyString;

	/**
	 * This is the cacheHandler being used by the system. This class handles all of the data processing, but the actual
	 * storage is done by a seperate handler, allowing different options for caching.
	 *
	 * @var cacheHandler
	 */
	protected $handler;

	/**
	 * If this flag is set to true the cache record is only stored in the scripts memory, not persisted.
	 *
	 * @var bool
	 */
	protected $memOnly = false;

	/**
	 * This is the name of the cache handler the system is using to store data.
	 *
	 * @var string
	 */
	protected static $handlerClass = '';

	/**
	 * This is an array of possible cache storage data methods, with the handler class as the array value.
	 *
	 * @var array
	 */
	protected static $handlers = array('FileSystem' => 'StashFileSystem',
										'SQLiteMF' => 'StashSqlite',
										'SQLite' => 'StashSqliteOneFile');
	/**
	 * This variable can be used to disable the cache system wide. It is used when the storage engine fails or if the
	 * cache is being cleared.
	 *
	 * @var bool
	 */
	static $runtimeDisable = false;

	/**
	 * This is a running count of how many times the cache has been called
	 *
	 * @var int
	 */
	static $cacheCalls = 0;

	/**
	 * This is a running count of how many times the cache was able to successfully retrieve current data from the
	 * cache.
	 *
	 * @var int
	 */
	static $cacheReturns = 0;

	/**
	 * This array holds a copy of all valid data (whether retrieved from or stored to the cacheHandler) in order to
	 * avoid unnecessary calls to the storage handler. The index of this array is the string version of the key, and
	 * the value is an exact copy of the data stored by the handlers. When items are added or removed this array gets
	 * updated automatically.
	 *
	 * @var string
	 */
	static $memStore = array();

	/**
	 * This keeps track of how many times a specific cache item is called. The array is the string version of the key
	 * and the value is the number of times it has been called.
	 *
	 * @var array
	 */
	static $queryRecord;

	/**
	 * This constructor requires a StashHandler.
	 *
	 * @param StashHandler $key...
	 */
	public function __construct(StashHandler $handler)
	{
		if((defined('DISABLECACHE') && DISABLECACHE) || self::$runtimeDisable)
			$this->cache_enabled = false;

		$this->handler = $handler;
	}

	/**
	 * This constructor takes an unlimited number of arguments. These strings should be unique to the data you are
	 * trying to store or retrieve. These keys should be considered hierarchical- that is, each additional argument
	 * passed is considered a child of the one before it by the system. This function stores that key and sets up the
	 * cacheHandler object to work with the data, although it does not retrieve it yet.
	 *
	 * @example $cache = new Cache('permissions', 'user', '4', '2'); where 4 is the user id and 2 is the location id.
	 *
	 * @param string $key...
	 */
	public function setupKey()
	{
		if(func_num_args() == 0)
			throw new StashError('No key sent to the cache constructor.');

		$key = func_get_args();
		if(count($key) == 1 && is_array($key[0]))
			$key = $key[0];

		$this->key = array_map('strtolower', $key);
		$this->keyString = implode(':::', $this->key);
	}

	/**
	 * This function makes it so the data object is only stored for the duraction of the script. This is used for things
	 * that may be different from each script or that require less overhead to generate than store, but are called
	 * enough times to justify storing each script.
	 *
	 */
	public function setMemOnly()
	{
		$this->memOnly = true;
	}

	/**
	 * This takes the same argument as the constructor, specifically an unlimited number of strings that are used to
	 * define the key. Unlike the constructor, this function affects multiple items- the key used used hierarchical and
	 * the less arguments passed, the more data that will be cleared. No arguments passed clears the cache complete.
	 * This function works by passing the request to the cacheHandler.
	 *
	 * @example Stash::clear('permissions', 'user', '4', '2'); will clear the permissions for the user with the id of 4,
	 * at the location with the id 2.
	 * @example Stash::clear('permissions', 'user', '4'); will clear the permissions of the user with the id of 4 at all
	 * locations.
	 * @example Stash::clear('permissions', 'user'); will clear the permissions for all users, at all locations.
	 *
	 * @param null|string $key...
	 * @return bool
	 */
	public function clear()
	{
		try{
			if((defined('DISABLECACHE') && DISABLECACHE) || self::$runtimeDisable)
				return true;

			self::$memStore = array();

			if($handler = $this->getHandler())
			{
				$args = func_get_args();
				return $handler->clear($args);
			}

		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * The purge function removes all expired or stale data from the cache system. It may also perform other cleanup
	 * actions depending on the cache handler used.
	 *
	 * @return bool
	 */
	public function purge()
	{
		try{
			self::$memStore = array();
			if($handler = $this->getHandler())
				return $handler->purge();

		}catch(Exception $e){

		}

		return false;
	}

	/**
	 * This function returns the data retrieved from the cache. This can be any php datatype that is able to be
	 * serialized. Because this can return false as a correct, cached value, the return value should not be used to
	 * determine successful retrieval of data.
	 *
	 * @return mixed
	 */
	public function getData()
	{
		self::$cacheCalls++;

		if(!$this->cache_enabled)
			return null;

		try
		{
			if(isset(self::$memStore[$this->keyString]) && is_array(self::$memStore[$this->keyString]))
			{
				$record = self::$memStore[$this->keyString];
			}elseif(!$this->memOnly){
				$handler = $this->getHandler();
				if(!$handler)
					return null;

				$record = $handler->getData($this->key);

				if(!is_array($record))
					return null;

				if($this->storeMemory)
					self::$memStore[$this->keyString] = $record;

			}else{
				return null;
			}

			if(!isset($record['expiration']) || $record['expiration'] - microtime(true) < 0)
				return null;

			$this->cacheReturned = true;
			self::$cacheReturns++;
			return $record['data']['return'];

		}catch(Exception $e){
			$this->cache_enabled = false;
			return null;
		}
	}

	/**
	 * This function can be used to see if a cached object is fresh or stale.
	 *
	 * @return bool
	 */
	public function isStale()
	{
		return !$this->cacheReturned;
	}

	/**
	 * This function takes in any php datatype, including properly defined classes (must be able to serialize), and
	 * stores it for later retrieval. It adds an expiration date (current time plus the cacheTime value, with a small
	 * random addition or subtraction from that value to better randomize, and distribute, failed hits and thus
	 * heavier code).
	 *
	 * @param mixed bool
	 */
	public function storeData($data)
	{
		if(!$this->cache_enabled)
			return;

		$store['return'] = $data;
		$store['createdOn'] = microtime(true);

		try{
			$expiration = (microtime(true) + ($this->cacheTime - rand(0, $this->cacheTime * .15)));

			if($this->storeMemory)
				self::$memStore[$this->keyString] = array('expiration' => $expiration, 'data' => $store);

			if($this->memOnly)
				return true;

			$handler = $this->getHandler();
			if(!$handler)
				return false;

			$handler->storeData($this->key, $store, $expiration);
		}catch(Exception $e){
			return false;
		}
	}

	/**
	 * This function extends the expiration on the current cached item.
	 *
	 * @return bool
	 */
	public function extendCache()
	{
		if(!$this->cache_enabled)
			return;

		return $this->storeData(self::$memStore[$this->keyString]['data']['return']);
	}

	/**
	 * This returns a list of available cache handlers that can currently be enabled.
	 *
	 * @return unknown
	 */
	static function getHandlers()
	{
		foreach(self::$handlers as $name => $class)
		{
			if(!class_exists($class))
				continue;

			if(StashUtilities::staticFunctionHack($class, 'canEnable'))
				$availableHandlers[$name] = $class;
		}

		return $availableHandlers;
	}

	/**
	 * This function returns cache handler for use by this class.
	 *
	 * @return cacheHandler
	 */
	protected function getHandler()
	{
		if($this->cache_enabled != true)
			return false;

		if(isset($this->handler))
			return $this->handler;

		return false;
	}
}

?>