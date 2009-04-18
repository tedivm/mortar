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
	protected $memberGroups = array();

	public function __construct($userId = null)
	{
		if($userId)
			$this->load_user($userId);
	}

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
 * Active User Class
 *
 * Controls the properties of the active user
 *
 * @package		BentoBase
 * @subpackage	Main_Classes
 * @category	User
 * @author		Robert Hafner
 */
class ActiveUser implements SplSubject
{
	private static $instance;
	private $observers = array();
	protected $user;


	/**
	 * Constructor - private, call by get_instance
	 */
	private function __construct()
	{
		// By default you're a guest
		$this->loadUserByName('guest');
	}

	public function loadUser($id)
	{
		if(!is_numeric($id))
			throw new TypeMismatch(array('int', $id));

		$user = new User();
		if($user->load_user($id))
		{
			$this->user = $user;
			$this->notify();
			return true;
		}else{
			return false;
		}

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
			self::$instance = new $object();
		}
		return self::$instance;
	}

	public static function get_instance()
	{
		return self::getInstance();
	}

	public function __destruct()
	{
		$this->notify();
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




	public function attach(SplObserver $class)
	{
		$this->observers[] = $class;
		$class->update($this);
	}

	public function detach(SplObserver $obj)
	{
		foreach ($this->observers as $index => $class)
		{
			if($class == $obj)
				unset($this->observers[$index]);

		}
	}

	public function notify()
	{
		foreach ($this->observers as $observers)
			$observers->update($this);
	}

}

?>