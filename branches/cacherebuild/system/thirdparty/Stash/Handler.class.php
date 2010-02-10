<?php

/**
 * This interface defines the standard for cacheHandlers. When writing new cache storage engines, this is the place to
 * start.
 *
 * @package System
 * @subpackage Caching
 */
interface StashHandler
{
	/**
	 * This function gets the key, as an array. It should save it and make sure that it is able to run. If this function
	 * returns anything but true the cache will be disabled for that request.
	 *
	 * @param array $key
	 * @return bool
	 */
	public function setup($key); // return boolean

	/**
	 * This function should return the data array, exactly as it was received by the storeData function, or false if it
	 * is not present. This array should have a value for "createdOn" and for "return", which should be the data the
	 * main script is trying to store.
	 *
	 * @return array
	 */
	public function getData();

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
	public function storeData($data, $expiration);

	/**
	 * This function should clear the cache tree using the key array provided. If called with no arguments the entire
	 * cache needs to be cleared.
	 *
	 * @param null|array $key
	 * @return bool
	 */
	static function clear($key = null);

	static function purge();
}

?>