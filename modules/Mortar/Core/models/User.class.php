<?php

class MortarCoreModelUser extends ModelBase
{
	static public $type = 'User';
	protected $table = 'users';

	/**
	 * This function runs the parent function before updating the User's membergroups.
	 *
	 * @return bool
	 */
	public function save()
	{
		try
		{
			if(parent::save())
			{
				$db = DatabaseConnection::getConnection('default');
				$deleteStmt = $db->stmt_init();
				$deleteStmt->prepare('DELETE FROM userInMemberGroup WHERE user_id = ?');
				$deleteStmt->bindAndExecute('i', $this->id);

				if(isset($this->content['membergroups']))
					foreach($this->content['membergroups'] as $id)
				{
					$insertMemgroupStmt = $db->stmt_init();
					$insertMemgroupStmt->prepare('INSERT INTO userInMemberGroup (user_id, memgroup_id) VALUES (?,?)');
					$insertMemgroupStmt->bindAndExecute('ii', $this->id, $id);
				}
				$db->commit();

			}else{
				throw new Exception();
			}

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			return false;
		}

		$db->autocommit(true);
		return true;
	}

	/**
	 * This function runs the parent function to load the user information and then loads the user's membergroups into
	 * an array.
	 *
	 * @cache models User *id membegroups
	 * @param int $id
	 * @return bool
	 */
	protected function load($id)
	{
		if(parent::load($id))
		{
			$cache = CacheControl::getCache('models', 'User', $id, 'membergroups');
			$memberGroups = $cache->getData();
			if($cache->isStale())
			{
				$db = db_connect('default_read_only');
				$db = DatabaseConnection::getConnection('default_read_only');

				$stmtMemberGroups = $db->stmt_init();
				$stmtMemberGroups->prepare('SELECT memgroup_id FROM userInMemberGroup WHERE user_id = ?');
				$stmtMemberGroups->bindAndExecute('i', $id);

				if($stmtMemberGroups->num_rows > 0)
				{
					$memberGroups = array();
					while($memgroup = $stmtMemberGroups->fetch_array())
					{
						$memberGroups[] = $memgroup['memgroup_id'];
					}

				}else{
					$memberGroups = array();
				}
				$cache->storeData($memberGroups);
			}

			$this->content['membergroups'] = $memberGroups;
			return true;
		}else{
			return false;
		}

		return false;
	}

	/**
	 * This function can be used to load a user based on a username.
	 *
	 * @cache models *type loadByName *address
	 * @param string $name
	 * @return bool
	 */
	public function loadbyName($name)
	{
		$cache = CacheControl::getCache('models', $this->getType(), 'loadByName', $name);
		$userId = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT user_id FROM users WHERE name = ?');
			$stmt->bindAndExecute('s', $name);

			$userId = ($results = $stmt->fetch_array()) ? $results['user_id'] : false;
			$cache->storeData($userId);
		}

		if(!is_numeric($userId))
			return false;

		return $this->load($userId);
	}

	/**
	 * This function can be used to load a user from an email address. It looks up the user id based on the email
	 * address and then runs the load function.
	 *
	 * @cache models *type loadByEmail *address
	 * @param string $address
	 * @return bool
	 */
	public function loadByEmail($address)
	{
		if(!filter_var($address, FILTER_VALIDATE_EMAIL))
			throw new CoreError('You must pass an email address to the loadByEmail function.');

		$cache = CacheControl::getCache('models', $this->getType(), 'loadByEmail', $address);
		$userId = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT user_id FROM users WHERE email = ?');
			$stmt->bindAndExecute('s', $address);

			$userId = ($results = $stmt->fetch_array()) ? $results['user_id'] : false;
			$cache->storeData($userId);
		}

		if(!is_numeric($userId))
			return false;

		return $this->load($userId);
	}

	/**
	 * This function intercepts calls to the parent class and function in order to encrypt the password before saving
	 * it.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function offsetSet($name, $value)
	{
		if($name == 'password')
		{
			$password = new Password();
			$password->fromString($value);
			$value = $password->getStored();
		}
		return parent::offsetSet($name, $value);
	}

}

?>