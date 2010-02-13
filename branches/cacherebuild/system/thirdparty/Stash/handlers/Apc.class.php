<?php

class StashApc implements StashHandler
{
	protected $cacheTime = 300;

	/**
	 * This function should takes an array which is used to pass option values to the handler.
	 *
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		if(isset($options['ttl']) && is_numeric($options['ttl']))
			$this->cacheTime = (int) $options['ttl'];

		$adjust = .1 * $this->cacheTime;
		$this->cacheTime =+ rand($adjust * -1, $adjust);
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

		$data = apc_fetch($keyString, $success);
		if(!$success)
			return false;

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

		return apc_add($keyString, serialize(array('return' => $data, 'expiration' => $expiration)), $this->cacheTime);
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
		return apc_clear_cache('user');
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
		return extension_loaded('apc');
	}

	static protected function makeKey($key)
	{
		if(!is_array($key) || count($key) < 1)
			return false;

		$keyString = md5(__file__); // make it unique per install

		foreach($key as $piece)
			$key .= '::' . $piece;

		return $keyString;
	}
}

?>