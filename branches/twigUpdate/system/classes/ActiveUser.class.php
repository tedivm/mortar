<?php
/**
 * Mortar
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
			self::changeUserByName('Guest');

		return self::$user;
	}

	/**
	 * This function changes the active user to one matching the passed name. This is most commonly used for special
	 * accounts (Guest, System, Admin, and Cron) and should be avoided in favor of changeUserById.
	 *
	 * @param string $name
	 * @return bool
	 */
	static function changeUserByName($name)
	{
		$userId = self::getIdFromName($name);
		return self::changeUserById($userId);
	}

	/**
	 * This function takes in a username and password (string, not class) to authenticate and change the active user to.
	 * If the username isn't found or the password does not match the Guest user is set to active. In cases where the
	 * password does match but is stored in an older format the password will be resaved in the new format (since this
	 * is the only time we have access to the plaintext password).
	 *
	 * When we begin moving the authentication stuff to its own plugin architecture this function will be moved out of
	 * this class.
	 *
	 * @param string $name
	 * @param string $password
	 * @return bool
	 */
	static function changeUserByNameAndPassword($name, $password)
	{
		$userId = self::getIdFromName($name);
		$user = ModelRegistry::loadModel('User', $userId);

		if($user['allowlogin'] != 1 && $user['allowlogin'] !== true)
		{
			self::changeUserByName('Guest');
			return false;
		}

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
			self::changeUserByName('Guest');
			return false;
		}
	}

	/**
	 * This function loads the user model based off of the ID passed. All of the "changeUser" functions ultimately
	 * rely on this one.
	 *
	 * @param int $id
	 * @return bool
	 */
	static function changeUserById($id)
	{
		try
		{
			if($user = ModelRegistry::loadModel('User', $id))
			{
				self::$user = $user;
				self::notify();
				return true;
			}else{
				self::changeUserByName('Guest');
				return false;
			}

		}catch(Exception $e){
			self::changeUserByName('Guest');
			return false;
		}
	}

	/**
	 * This function returns the users ID from their name.
	 *
	 * @cache userLookup byname *name
	 * @param string $name
	 * @return int
	 */
	static function getIdFromName($name)
	{
		$cache = new Cache('userLookup', 'byname', $name);
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

	/**
	 * Returns true if the user is logged in, false otherwise.
	 *
	 * @return bool
	 */
	static public function isLoggedIn()
	{
		$user = self::getUser();
		$name = strtolower($user['name']);
		return $name != 'guest';
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