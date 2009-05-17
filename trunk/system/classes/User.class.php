<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage User
 */

/**
 * This class contains all the data about a user
 *
 * @package System
 * @subpackage User
 */
class User
{

	/**
	 * The user's unique id
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Stored password string
	 *
	 * @access protected
	 * @var string
	 */
	protected $password;

	/**
	 * user name
	 *
	 * @access protected
	 * @var string
	 */
	protected $username;

	/**
	 * user's email address
	 *
	 * @access protected
	 * @var int
	 */
	protected $email;

	/**
	 * flag deciding if user can log in or not
	 *
	 * @access protected
	 * @var bool
	 */
	protected $allowLogin;

	/**
	 * List of membergroups the user belongs to
	 *
	 * @access protected
	 * @var array
	 */
	protected $memberGroups = array();

	/**
	 * Constuctor takes the user id as an optional argument, and if passed it loads the user info
	 *
	 * @param int $userId
	 */
	public function __construct($userId = null)
	{
		if($userId)
			$this->loadUser($userId);
	}

	/**
	 * Load user by ID
	 *
	 * @cache users *userId lookup
	 * @param int $id
	 */
	public function loadUser($userId)
	{
		$this->id = false;
		$this->user_info = array();

		if($userId instanceof User)
			throw new BentoError();

		$cache = new Cache('users', $userId, 'lookup');

		$info = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$db = DatabaseConnection::getConnection('default_read_only');

			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
			$stmt->bindAndExecute('i', $userId);

			if($stmt->num_rows == 1)
			{

				$info = $stmt->fetch_array();

				$stmtMemberGroups = $db->stmt_init();
				$stmtMemberGroups->prepare('SELECT memgroup_id FROM userInMemberGroup WHERE user_id = ?');
				$stmtMemberGroups->bindAndExecute('i', $userId);

				if($stmtMemberGroups->num_rows > 0)
				{
					$memberGroups = array();
					while($memgroup = $stmtMemberGroups->fetch_array())
					{
						$memberGroups[] = $memgroup['memgroup_id'];
					}
					$info['membergroups'] = $memberGroups;
				}

			}else{
				$info = false;
			}

			$cache->storeData($info);
		}

		if($info != false)
		{
			$this->id = $userId;
			$this->password = $info['user_password'];
			$this->username = $info['user_name'];
			$this->email = $info['user_email'];
			$this->allowLogin = $info['user_allowlogin'];
			$this->memberGroups = $info['membergroups'];
			return $userId;
		}else{
			return false;
		}

		return false;
	}

	/**
	 * Returns the username
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->username;
	}

	/**
	 * Checks to see if the user is allowed to log in
	 *
	 * @return bool
	 */
	public function isAllowedLogin()
	{
		return ($this->allowLogin == true);
	}

	/**
	 * Changes the users name
	 *
	 * @param string $name
	 */
	public function setName($name)
	{
		if(!is_string($name))
			throw new TypeMismatch(array('string', $name));
		// Check for a charactor that isn't
		if(ereg('[^A-Za-z0-9_ \-]', $name))
			throw new BentoWarning('Username must be alphanumeric or be a space, underscore or hyphen. Attempted username: ' . $name);

		$this->username = $name;

	}

	/**
	 * Returns the users email address
	 *
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * Sets the users email address
	 *
	 * @param unknown_type $email
	 */
	public function setEmail($email)
	{
		if(!is_string($email))
			throw new TypeMismatch(array('string', $email));
		// Check for a charactor that isn't

		$this->email = $email;
	}

	/**
	 * Changes the users password
	 *
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$passwordObject = new Password();
		$passwordObject->fromString($password);
		$this->password = $passwordObject->getStored();
	}

	/**
	 * Returns the users id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Returns an array with the users membergroups
	 *
	 * @return unknown
	 */
	public function getMemberGroups()
	{
		return $this->memberGroups;
	}

	/**
	 * Replaces the current list of membergroups with the passed array
	 *
	 * @param array $groups
	 */
	public function setMemberGroups($groups)
	{
		depreciationWarning();
		$this->memberGroups = $groups;
	}

	/**
	 * Toggles the permission for the user to log in
	 *
	 * @param bool $isAllowed
	 */
	public function setAllowLogin($isAllowed)
	{
		$this->allowLogin = ($isAllowed === true);
	}

	/**
	 * Saved the User information to the database
	 *
	 * @return bool
	 */
	public function save()
	{
		$db = DatabaseConnection::getConnection('default');
		$db->autocommit(false);
		try{

			$stmt = $db->stmt_init();
			if(!is_numeric($this->id))
			{
				$stmt->prepare('INSERT INTO users (user_id, user_name, user_password, user_email, user_allowlogin)
												VALUES (NULL, ?, ?, ?, ?)');
				if(!$stmt->bindAndExecute('sssi', $this->username, $this->password,
											 $this->email, ($this->allowLogin) ? 1 : 0))
				{
					throw new BentoWarning('Unable to add user to database');
				}

				$this->id = $stmt->insert_id;
			}else{
				$stmt->prepare('UPDATE users SET user_name = ?, user_password = ?, user_email = ?, user_allowlogin = ?
										WHERE user_id = ?');

				if(!$stmt->bindAndExecute('sssii', $this->username, $this->password,
											 $this->email, ($this->allowLogin) ? 1 : 0, $this->id))
				{
					throw new BentoWarning('Unable to update user.');
				}
			}

			$deleteStmt = $db->stmt_init();
			$deleteStmt->prepare('DELETE FROM userInMemberGroup WHERE user_id = ?');
			$deleteStmt->bindAndExecute('i', $this->id);

			foreach($this->memberGroups as $id)
			{
				$insertMemgroupStmt = $db->stmt_init();
				$insertMemgroupStmt->prepare('INSERT INTO userInMemberGroup (user_id, memgroup_id) VALUES (?,?)');
				$insertMemgroupStmt->bindAndExecute('ii', $this->id, $id);
			}

			$db->commit();

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}

		$db->autocommit(true);
		return true;
	}

}




/**
 * This needs to be cleaned up. Its a singleton containing the active user, as well as some wrapper functions around
 * said user
 *
 * @package System
 * @subpackage Environment
 */
class ActiveUser implements SplSubject
{
	/**
	 * Instance of active user
	 *
	 * @access private
	 * @static
	 * @var ActiveUser
	 */
	private static $instance;

	/**
	 * These objects get notified during certain events, such as the changing of the active user
	 *
	 * @access private
	 * @var array
	 */
	private $observers = array();

	/**
	 * This is the current user
	 *
	 * @access protected
	 * @var User
	 */
	protected $user;


	/**
	 * Constructor loads the "guest" user to start
	 *
	 * @access protected
	 */
	private function __construct()
	{
		// By default you're a guest
		$this->loadUserByName('guest');
	}

	/**
	 * Load a user by its id
	 *
	 * @param int $id
	 * @return bool
	 */
	public function loadUser($id)
	{
		if(!is_numeric($id))
			throw new TypeMismatch(array('int', $id));

		$user = new User();
		if($user->loadUser($id))
		{
			$this->user = $user;
			$this->notify();
			return true;
		}else{
			return false;
		}

	}

	/**
	 * Change the user, if the password matches, otherwise load the guest user
	 *
	 * @param string $userName
	 * @param string $password
	 * @return bool
	 */
	public function changeUser($userName, $password)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT * FROM users WHERE user_name=?");
		$stmt->bindAndExecute('s', $userName);
		$numRows = $stmt->num_rows;
		$user_array = $stmt->fetch_array();

		$stmt->close();
		$storedPassword = new Password();
		$storedPassword->fromStored($user_array['user_password']);

		if($numRows == 1 && $storedPassword->isMatch($password))
		{
			if($this->loadUser($user_array['user_id']))
			{
				return true;
			}else{

				$this->loadUserByName('guest');
			}
		}else{
			$this->loadUserByName('guest');
		}

		return false;
	}


	/**
	 * This function checks to see if the user is logged in or is using a guest account
	 *
	 * @static
	 * @return bool
	 */
	static public function isLoggedIn()
	{
		$user = self::getInstance();
		return ($user->getName() != 'guest');
	}


	/**
	 * Returns the current user object
	 *
	 * @static
	 * @return User
	 */
	public static function getCurrentUser()
	{
		$self = self::getInstance();
		return $self->getUser();
	}

	/**
	 * Returns the stored instance of the ActiveUser. If no object
	 * is stored, it will create it
	 *
	 * @static
	 * @return ActiveUser
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object();
		}
		return self::$instance;
	}

	/**
	 * Returns the current ActiveUser instance
	 *
	 * @deprecated
	 * @see getInstance()
	 * @static
	 * @return unknown
	 */
	public static function get_instance()
	{
		return self::getInstance();
	}

	/**
	 * Returns the current user (non-static version)
	 *
	 * @return User
	 */
	public function getUser()
	{
		return $this->user;
	}


	/**
	 * Loads user by username
	 *
	 * @cache username *user id
	 * @param string $user
	 * @return bool
	 */
	public function loadUserByName($user)
	{
		$cache = new Cache('username', $user, 'id');

		$id = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT * FROM users WHERE user_name=? LIMIT 1");
			$stmt->bindAndExecute('s', $user);

			if($stmt->num_rows == 1)
			{
				$array = $stmt->fetch_array();
				$id = $array['user_id'];
			}else{
				$id = false;
			}
			$cache->storeData($id);
		}

		if($id === false)
			return false;

		return $this->loadUser($id);
	}

	/**
	 * Returns the username
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->user->getName();
	}

	/**
	 * Returns the user id
	 *
	 * @return int
	 */
	public function getId()
	{
		return $this->user->getId();
	}

	/**
	 * Returns an array of membergroups that the user belongs to
	 *
	 * @return array
	 */
	public function getMemberGroups()
	{
		return $this->user->getMemberGroups();
	}

	/**
	 * Attachs an observer to monitor the active user
	 *
	 * @param SplObserver $class
	 */
	public function attach(SplObserver $class)
	{
		$this->observers[] = $class;
		$class->update($this);
	}

	/**
	 * Reomoves an observer from watching the actuve user
	 *
	 * @param SplObserver $obj
	 */
	public function detach(SplObserver $obj)
	{
		foreach ($this->observers as $index => $class)
		{
			if($class == $obj)
				unset($this->observers[$index]);

		}
	}

	/**
	 * Notifies each observer (by sending a copy of this class to it) of changes in the system
	 *
	 */
	public function notify()
	{
		foreach ($this->observers as $observers)
			$observers->update($this);
	}

}

?>