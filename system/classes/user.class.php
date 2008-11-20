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
		
	/**
	 * Load user by ID
	 *
	 * @param int $id
	 */
	public function load_user($userId)
	{
		
		$this->id = false;
		$this->user_info = array();
		
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
					
			$cache->store_data($info);
		}
		
		if($info != false)
		{
			$this->id = $userId;
			$this->password = $info['user_password'];
			$this->username = $info['user_name'];
			$this->email = $info['user_email'];
			$this->allowLogin = $info['user_allowlogin'];
			return $userId;
		}else{
			return $this->load_user_by_username('guest');
		}
		
		return false;
	}
	
	public function getName()
	{
		return $this->username;
	}
	

	public function getEmail()
	{
		return $this->email;
	}


	public function getId()
	{
		return $this->id;
	}

	
	/**
	 * Load user from by username and password
	 *
	 * @param string $user
	 * @param string $pass
	 */
	public function load_user_by_userpass($user, $pass)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT * FROM users WHERE user_name=?");
		$stmt->bind_param_and_execute('s', $user);
		$numRows = $stmt->num_rows;
		$user_array = $stmt->fetch_array();
		
		$stmt->close();
		$password = new StoredPassword($user_array['user_password']);
		
		if($numRows == 1 && $password->is_match($pass))
		{
			$this->clear_user();
			return $this->load_user($user_array['user_id']);
		}

		return false;

	}	
	
	public function load_user_by_username($user)
	{
		return $this->loadUserByName($user);
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

		if(is_numeric($id))
			return $this->load_user($id);
		
		return false;
		
	}	
	
	/**
	 * Return user information
	 *
	 * @param string $param
	 * @return mixed
	 */
	public function setting($param)
	{
		return $this->user_info[$param];
	}
	
	protected function clear_user()
	{
		unset($this->id);
		unset($this->password);
		unset($this->username);
		unset($this->email);
		unset($this->allowLogin);
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
class ActiveUser extends User 
{
	private static $instance;



	/**
	 * Constructor - private, call by get_instance
	 */
	private function __construct()
	{
		try{
			
			if(!is_numeric($_SESSION['user_id']))
				throw new BentoNotice('No session started.');
			
			if($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
				throw new BentoNotice('Useragent mixmatch (possible session hijacking attempt).');
			
			if($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
				throw new BentoNotice('IP Address mixmatch (possible session hijacking attempt).');
							
			$this->load_user($_SESSION['user_id']);
			
		}catch(Exception $e){
			$this->load_user_by_username('guest');
		}
		
		$this->loggedIn = ($this->username != 'guest');
	}
	
	public function load_user($id)
	{
		$result = parent::load_user($id);
		$this->sessionStart(true);
		return $result;
	}
	protected function sessionStart($reload = false)
	{
		$_SESSION['nonce'] = $this->id;
		
		if(!isset($_SESSION['nonce']) || $reload)
			$_SESSION['nonce'] = md5($this->id . START_TIME);
		
		if(!isset($_SESSION['IPaddress']) || $reload)
			$_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
		
		if(!isset($_SESSION['userAgent']) || $reload)
			$_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];

		
		$_SESSION['user_id'] = $this->id;
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
	
	public function change_user($user, $password)
	{
		return $this->load_user_by_userpass($user, $password);
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
		$_SESSION['user_id'] = $this->id;
	}

}

?>