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
 * This class is used by the Cache class for persistent storage of cached objects using an sqlite file.
 *
 * @package System
 * @subpackage Caching
 */
class StashSqliteOneFile extends StashSqlite
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
	public function clear($key = null)
	{
		if(is_null($key) || (is_array($key) && count($key) == 0))
		{
			StashUtilities::deleteRecursive($this->cachePath);
			self::$sqlObject = false;
			Stash::$runtimeDisable = true;
		}else{
			$key = self::makeSqlKey($key) . '%';
			$sqlResource = $this->getSqliteHandler($key[0]);
			$query = $sqlResource->exec("DELETE FROM cacheStore WHERE key LIKE '{$key}'");
		}
	}

	/**
	 * Removes all stale columns from the sqlite database.
	 *
	 * @return unknown
	 */
	public function purge()
	{
		$handler = self::getSqliteHandler('cache');
		$handler->query('DELETE FROM cacheStore WHERE expires < ' . microtime(true));
		return true;
	}

	/**
	 * This function is used to retrieve an SQLiteDatabase object. If the requested section does not exist, it creates
	 * and and sets up the structure.
	 *
	 * @param string
	 * @return bool
	 */
	public function getSqliteHandler($name)
	{
		return parent::getSqliteHandler('cache');
	}
}

?>