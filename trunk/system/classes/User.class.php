<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage User
 */

class ActiveUser
{
	static protected $user;

	/**
	 * These objects get notified during certain events, such as the changing of the active user
	 *
	 * @access private
	 * @var array
	 */
	static protected $observers = array();

	static function getUser()
	{
		if(!isset(self::$user))
			self::changeUserByName('guest');

		return self::$user;
	}

	static function changeUserByName($name)
	{
		$userId = self::getIdFromName($name);
		return self::changeUserById($userId);
	}

	static function changeUserByNameAndPassword($name, $password)
	{
		$userId = self::getIdFromName($name);
		$user = ModelRegistry::loadModel('User', $userId);

		$storedPassword = new Password();
		$storedPassword->fromStored($user['password']);

		if($storedPassword->isMatch($password))
		{
			// If the password is not stored with the current password settings, restore the password to it gets
			// hashed properly.
			if(!$storedPassword->isCurrent())
			{
				$user['password'] = $password;
				$user->save();
			}

			self::changeUserById($userId);
			return true;
		}else{
			self::changeUserByName('guest');
			return false;
		}
	}

	static function changeUserById($id)
	{
		try {
			$user = ModelRegistry::loadModel('User', $id);
			self::$user = $user;
			self::notify();
			return true;
		}catch(Exception $e){
			self::changeUserByName('guest');
			return false;
		}
	}

	static function getIdFromName($name)
	{
		$cache = new Cache('userLookup', $name, 'id');
		$userId = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT user_id FROM users WHERE name=?");
			$stmt->bindAndExecute('s', $name);

			if($stmt->num_rows == 1 && $userRow = $stmt->fetch_array())
			{
				$userId = $userRow['user_id'];
			}else{
				$userId = false;
			}
			$cache->storeData($userId);
		}
		return $userId;
	}

	static public function isLoggedIn()
	{
		$user = self::getUser();
		return ($user['name'] != 'guest');
	}

	/**
	 * Attachs an observer to monitor the active user
	 *
	 * @param SplObserver $class
	 */
	static public function attach($class)
	{
		self::$observers[] = $class;
		$class->update(self::getUser());
	}

	/**
	 * Reomoves an observer from watching the actuve user
	 *
	 * @param SplObserver $obj
	 */
	static public function detach(SplObserver $obj)
	{
		foreach (self::$observers as $index => $class)
		{
			if($class == $obj)
				unset(self::$observers[$index]);
		}
	}

	/**
	 * Notifies each observer (by sending a copy of this class to it) of changes in the system
	 *
	 */
	static public function notify()
	{
		foreach(self::$observers as $observers)
			$observers->update(self::$user);
	}

}

?>