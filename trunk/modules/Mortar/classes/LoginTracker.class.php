<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package Mortar
 * @subpackage Classes
 */

/**
 * This class tracks login failures by user id and ip address to allow the login action to protect against brute force
 * attacks. This is done by using an SQLite database, stored in the /tmp directory.
 *
 * @package Mortar
 * @subpackage Classes
 */
class MortarLoginTracker
{

	/**
	 * This is the time, in seconds, that a login failure will be counted in the system.
	 *
	 * @var int
	 */
	static $defaultExpirationAge = 43200;

	/**
	 * This function clears failed login attempts that are older than the passed time (in seconds). If no value is
	 * passed the defaultExpirationAge static value is used instead.
	 *
	 * @param int $expirationTime
	 * @return bool
	 */
	static function purge($expirationTime = null)
	{
		if(!isset($expirationTime))
			$expirationTime = self::$defaultExpirationAge;

		$expiration = time() - $expirationTime;

		if(!$db = self::getHandler())
			return false;

		return ($db->query("DELETE FROM failedLoginAttempts WHERE time < {$expiration}") !== false);
	}

	/**
	 * This function clears all failures that came from the passed IP address or used the passed userid.
	 *
	 * @param int $ipAddress
	 * @param int $userId
	 * @return bool
	 */
	static function clearFailures($ipAddress, $userId)
	{
		if(!$db = self::getHandler())
			return false;

		$ip = sqlite_escape_string($ipAddress);
		$results = $db->query("DELETE FROM failedLoginAttempts WHERE ip = '{$ip}' OR user = '{$userId}'");
		return ($results === false);
	}

	/**
	 * This function returns the number of failures that match either the passed ipAddress or userId.
	 *
	 * @param int $ipAddress
	 * @param int $userId
	 * @return int
	 */
	static function getFailureCount($ipAddress, $userId = false)
	{
		if(!$db = self::getHandler())
			return 0;

		$ip = sqlite_escape_string($ipAddress);

		$time = time() - self::$defaultExpirationAge;

		if(!is_numeric($userId) || $userId < 1)
		{
			$results = $db->query("SELECT COUNT(*) AS count FROM failedLoginAttempts
														WHERE ip = '{$ip}' AND time > '{$time}'");
		}else{
			$results = $db->query("SELECT COUNT(*) AS count FROM failedLoginAttempts
												WHERE (ip = '{$ip}' OR user = '{$userId}') AND time > '{$time}'");
		}

		return ($results !== false && $resultArray = $results->fetch()) ? $resultArray['count'] : 0;
	}

	/**
	 * This function adds a failure to the system that will later be returned by the getFailureCount function.
	 *
	 * @param int $ipAddress
	 * @param int $userId
	 * @return bool
	 */
	static function addFailure($ipAddress, $userId = false)
	{
		if(!$db = self::getHandler())
			return false;

		$ip = sqlite_escape_string($ipAddress);
		$username = ($userId) ? sqlite_escape_string($username) : 0;
		$timestamp = time();

		$result = $db->query("INSERT INTO failedLoginAttempts (user, ip, time)
									VALUES ('{$username}', '{$ip}', '{$timestamp}')");

		return ($result !== false);
	}

	/**
	 * This function returns the SQLite handler. If one doesn't exist one is created with the appropriate schema.
	 *
	 * @return SQLiteDatabase
	 */
	static protected function getHandler()
	{
		if(!$db = SqliteConnection::getDatabase('loginTracker'))
		{
			$result = SqliteConnection::createDatabase('loginTracker', '
						CREATE TABLE failedLoginAttempts (
							user TEXT,
							ip TEXT,
							time INTEGER
						);
						CREATE INDEX ipIndex ON failedLoginAttempts (ip);
						CREATE INDEX userIndex ON failedLoginAttempts (user);');

			if(!$result)
				return false;

			if(!$db = SqliteConnection::getDatabase('loginTracker'))
				return false;
		}
		return $db;
	}
}

?>