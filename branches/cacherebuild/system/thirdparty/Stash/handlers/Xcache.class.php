<?php

class StashXcache extends StashApc
{
	protected $user;
	protected $password;

	public function __construct($options = array())
	{
		if(!isset($options['user'], $options['password']))
			throw new StashXcacheError('XCache handler requires a username and password');

		$this->user = $options['user'];
		$this->password = $options['password'];

		parent::__construct($options);
	}

	/**
	 * This function should return the data array, exactly as it was received by the storeData function, or false if it
	 * is not present. This array should have a value for "createdOn" and for "return", which should be the data the
	 * main script is trying to store.
	 *
	 * @return array
	 */
	public function getData($key)
	{
		$keyString = self::makeKey($key);
		if(!$keyString)
			return false;

		if(!xcache_isset($keyString))
			return false;

		$data = xcache_get($keyString);
		return unserialize($data);
	}

	/**
	 * This function takes an array as its first argument and the expiration time as the second. This array contains two
	 * items, "createdOn" describing the first time the item was called and "return", which is the data that needs to be
	 * stored. This function needs to store that data in such a way that it can be retrieced exactly as it was sent. The
	 * expiration time needs to be stored with this data.
	 *
	 * @param array $data
	 * @param int $expiration
	 * @return bool
	 */
	public function storeData($key, $data, $expiration)
	{
		$keyString = self::makeKey($key);
		if(!$keyString)
			return false;

		$cacheTime = self::getCacheTime();
		return xcache_set($keyString, serialize(array('return' => $data, 'expiration' => $expiration)), $cacheTime);
	}


	/**
	 * This function should clear the cache tree using the key array provided. If called with no arguments the entire
	 * cache needs to be cleared.
	 *
	 * @param null|array $key
	 * @return bool
	 */
	public function clear($key = null)
	{
		if(isset($key) && function_exists('xcache_unset_by_prefix'))
		{
			$keyString = self::makeKey($key);
			if(!$keyString)
				return false;

			// this is such a sexy function, soooo many points to xcache
			return xcache_unset_by_prefix($keyString);

		}else{

			// xcache loses points for its login choice, but not as many as it gained for xcache_unset_by_prefix
			$original = array();
			if(isset($_SERVER['PHP_AUTH_USER']))
				$original['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];

			if(isset($_SERVER['PHP_AUTH_PW']))
				$original['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];

			$_SERVER['PHP_AUTH_USER'] = $this->user;
			$_SERVER['PHP_AUTH_PW'] = $this->password;

			xcache_clear_cache(XC_TYPE_VAR, 0);

			if(isset($$original['PHP_AUTH_USER']))
			{
				$_SERVER['PHP_AUTH_USER'] = $original['PHP_AUTH_USER'];
			}else{
				unset($_SERVER['PHP_AUTH_USER']);
			}

			if(isset($original['PHP_AUTH_PW']))
			{
				$_SERVER['PHP_AUTH_PW'] = $original['PHP_AUTH_PW'];
			}else{
				unset($_SERVER['PHP_AUTH_PW']);
			}
		}

		return true;
	}

	/**
	 * This function is used to remove expired items from the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		return $this->clear();
	}

	/**
	 * This function checks to see if it is possible to enable this handler. This returns true no matter what, since
	 * this is the handler of last resort.
	 *
	 * @return bool true
	 */
	static function canEnable()
	{
		return extension_loaded('xcache');
	}
}

class StashXcacheError extends StashError {}
?>