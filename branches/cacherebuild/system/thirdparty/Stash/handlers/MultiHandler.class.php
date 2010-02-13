<?php

class StashMultiHandler extends StashHandler
{

	protected $handlers = array();

	/**
	 * This function should takes an array which is used to pass option values to the handler.
	 *
	 * @param array $options
	 */
	public function __construct($options = array())
	{
		if(!isset($options['handlers']) || is_array($options['handlers']) || count($options['handlers']) < 1)
			throw new StashMultiHandlerError('This handler requires secondary handlers to run.');

		foreach($options['handlers'] as $handler)
		{
			if(!(is_object($handler) && $handler instanceof StashHandler))
				throw new StashMultiHandlerError('Handler objects are expected to implement StashHandler');

			$this->handlers[] = $handler;
		}
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
		$failedHandlers = array();
		foreach($this->handlers as $handler)
		{
			if($data = $handler->getData($key))
			{
				foreach($failedHandlers as $failedHandler)
					$failedHandler->storeData($key, $data['returned'], $data['expiration']);

				break;
			}else{
				$failedHandlers[] = $handler;
			}
		}

		return $data;
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
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
			$return = ($return) ? $this->storeData($key, $data, $expiration) : false;

		return $return;
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
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
			$return = ($return) ? $this->clear($key) : false;

		return $return;
	}

	/**
	 * This function is used to remove expired items from the cache.
	 *
	 * @return bool
	 */
	public function purge()
	{
		$handlers = array_reverse($this->handlers);
		$return = true;
		foreach($handlers as $handler)
			$return = ($return) ? $this->purge() : false;

		return $return;
	}

	/**
	 * This function checks to see if it is possible to enable this handler. This returns true no matter what, since
	 * this is the handler of last resort.
	 *
	 * @return bool true
	 */
	static function canEnable()
	{
		return true;
	}
}

?>