<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright	Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */

/**
 * User Class
 *
 * Very basic user class, meant to be extended for specific purposes
 *
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	User
 * @author		Robert Hafner
 */
class User
{
//	protected $user_info;
	public $id;

	protected $password;
	protected $username;
	protected $email;
	protected $allowLogin;
	protected $memberGroups;

	/**
	 * Load user by ID
	 *
	 * @param int $id
	 */
	public function load_user($userId)
	{
		$this->id = false;
		$this->user_info = array();

		if($userId instanceof User)
			throw new BentoError();

		$cache = new Cache('users', $userId, 'lookup');

		$info = $cache->get_data();

		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
			$stmt->bind_param_and_execute('i', $userId);

			if($stmt->num_rows == 1)
			{

				$info = $stmt->fetch_array();
			}else{

				$info = false;
			}

			$stmtMemberGroups = $db->stmt_init();
			$stmtMemberGroups->prepare('SELECT memgroup_id FROM user_in_member_group WHERE user_id = ?');

			$stmtMemberGroups->bind_param_and_execute('i', $userId);

			if($stmtMemberGroups->num_rows > 0)
			{
				$memberGroups = array();
				while($memgroup = $stmtMemberGroups->fetch_array())
				{
					$memberGroups[] = $memgroup['memgroup_id'];
				}

				$info['membergroups'] = $memberGroups;
			}

			$cache->store_data($info);
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

	public function getName()
	{
		return $this->username;
	}

	public function isAllowedLogin()
	{
		return ($this->allowLogin == true);
	}

	public function setName($name)
	{
		if(!is_string($name))
			throw new TypeMismatch(array('string', $name));
		// Check for a charactor that isn't
		if(ereg('[^A-Za-z0-9_ \-]', $name))
			throw new BentoWarning('Username must be alphanumeric or be a space, underscore or hyphen. Attempted username: ' . $name);

		$this->username = $name;

	}

	public function getEmail()
	{
		return $this->email;
	}

	public function setEmail($email)
	{
		if(!is_string($email))
			throw new TypeMismatch(array('string', $email));
		// Check for a charactor that isn't

		$this->email = $email;
	}

	public function setPassword($password)
	{
		$passwordString = $password;
		$passwordObject = new NewPassword($password);
		$this->password = $passwordObject->stored;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getMemberGroups()
	{
		return $this->memberGroups;
	}

	public function setMemberGroups($groups)
	{
		$this->memberGroups = $groups;
	}

	public function setAllowLogin($input)
	{
		$this->allowLogin = ($input === true);
	}

	public function save()
	{
		$db = dbConnect('default');
		$db->autocommit(false);
		try{

			$stmt = $db->stmt_init();

			if(!is_numeric($this->id))
			{

				$stmt->prepare('INSERT INTO users (user_id, user_name, user_password, user_email, user_allowlogin)
												VALUES (NULL, ?, ?, ?, ?)');
				if(!$stmt->bind_param_and_execute('sssi', $this->username, $this->password,
											 $this->email, ($this->allowLogin) ? 1 : 0))
				{
					throw new BentoWarning('Unable to add user to database');
				}

				$this->id = $stmt->insert_id;

			}else{

				$stmt->prepare('UPDATE users SET user_name = ?, user_password = ?, user_email = ?, user_allowlogin = ?
										WHERE user_id = ?');

				if(!$stmt->bind_param_and_execute('sssii', $this->username, $this->password,
											 $this->email, ($this->allowLogin) ? 1 : 0, $this->id))
				{
					throw new BentoWarning('Unable to update user.');
				}
			}


			$deleteStmt = $db->stmt_init();
			$deleteStmt->prepare('DELETE FROM user_in_member_group WHERE user_id = ?');
			$deleteStmt->bind_param_and_execute('i', $this->id);


			foreach($this->memberGroups as $id)
			{
				$insertMemgroupStmt = $db->stmt_init();
				$insertMemgroupStmt->prepare('INSERT INTO user_in_member_group (user_id, memgroup_id) VALUES (?,?)');
				$insertMemgroupStmt->bind_param_and_execute('ii', $this->id, $id);
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
 * Active User Class
 *
 * Controls the properties of the active user
 *
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	User
 * @author		Robert Hafner
 */
class ActiveUser // extends User
{
	private static $instance;

	protected $user;


	/**
	 * Constructor - private, call by get_instance
	 */
	private function __construct()
	{
		if(!$this->checkSession())
			$this->loadUserByName('guest');

		$this->loggedIn = ($this->username != 'guest');
	}


	protected function checkSession()
	{
		try{
			if($_SESSION['OBSOLETE'] && ($_SESSION['EXPIRES'] < time()))
				throw new BentoWarning('Attempt to use expired session.');

			if(!is_numeric($_SESSION['user_id']))
				throw new BentoNotice('No session started.');

			if($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
				throw new BentoNotice('IP Address mixmatch (possible session hijacking attempt).');

			if($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
				throw new BentoNotice('Useragent mixmatch (possible session hijacking attempt).');

			if(!$this->loadUser($_SESSION['user_id']))
				throw new BentoWarning('Attempted to log in user that does not exist with ID: ' . $_SESSION['user_id']);

			if(!$_SESSION['OBSOLETE'] && mt_rand(1, 100) == 1)
			{
				$this->regenerateSession();
			}

		}catch(Exception $e){
			return false;
		}
		return true;
	}

	public function loadUser($id)
	{
		if(!is_numeric($id))
			throw new TypeMismatch(array('int', $id));

		$user = new User();
		if($user->load_user($id))
		{
			$this->user = $user;
			$this->regenerateSession(true);
			return true;
		}else{
			return false;
		}

	}

	public function session($session, $value = false)
	{

		if(is_array($session))
		{
			foreach($session as $name => $value)
			{
				$_SESSION[$name] = (!is_null($value)) ? $value : false;
			}
			return $this;

		}elseif(is_string($value)){

			$_SESSION[$session] = $value;
			return $this;
		}

		return $_SESSION[$session];
	}

	public function changeUser($userName, $password)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT * FROM users WHERE user_name=?");
		$stmt->bind_param_and_execute('s', $userName);
		$numRows = $stmt->num_rows;
		$user_array = $stmt->fetch_array();

		$stmt->close();
		$storedPassword = new StoredPassword($user_array['user_password']);


		if($numRows == 1 && $storedPassword->is_match($password))
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
	 * Returns the stored instance of the ActiveUser. If no object
	 * is stored, it will create it
	 *
	 * @return ActiveUser
	 */
	public static function getInstance()
	{
		if(!isset(self::$instance)){
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}

	public static function get_instance()
	{
		return self::getInstance();
	}


	public function __destruct()
	{
		$_SESSION['user_id'] = $this->user->getId();
	}

	public function loadUserByName($user)
	{

		$cache = new Cache('usersname', $user, 'id');

		$id = $cache->get_data();

		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT * FROM users WHERE user_name=? LIMIT 1");
			$stmt->bind_param_and_execute('s', $user);

			if($stmt->num_rows == 1)
			{
				$array = $stmt->fetch_array();
				$id = $array['user_id'];
			}else{
				$id = false;
			}
			$cache->store_data($id);
		}

		if($id === false)
			return false;

		return $this->loadUser($id);
	}

	public function getName()
	{
		return $this->user->getName();
	}

	public function getId()
	{
		return $this->user->getId();
	}

	public function getMemberGroups()
	{
		return $this->user->getMemberGroups();
	}

	protected function regenerateSession($reload = false)
	{
		// This token is used by forms to prevent cross site forgery attempts
		if(!isset($_SESSION['nonce']) || $reload)
			$_SESSION['nonce'] = md5($this->id . START_TIME);

		if(!isset($_SESSION['IPaddress']) || $reload)
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];

		if(!isset($_SESSION['userAgent']) || $reload)
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		$_SESSION['user_id'] = $this->user->getId();


		// Set current session to expire in 1 minute
		$_SESSION['OBSOLETE'] = true;
		$_SESSION['EXPIRES'] = time() + 60;

		// Create new session without destroying the old one
		session_regenerate_id(false);

		// Grab current session ID and close both sessions to allow other scripts to use them
		$newSession = session_id();
		session_write_close();

		// Set session ID to the new one, and start it back up again
		session_id($newSession);
		session_start();

		// Don't want this one to expire
		unset($_SESSION['OBSOLETE']);
		unset($_SESSION['EXPIRES']);
	}

	protected function sessionCleanUp()
	{

	}

}

?>