<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage User
 */

/**
 * This object represents a group of users
 *
 * @package System
 * @subpackage User
 */
class MemberGroup
{
	/**
	 * This is the name of the membergroup
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * This is the database id for the membergroup
	 *
	 * @var int
	 */
	protected $id;

	/**
	 * This marks a membergroup as being for internal use, not generally assignable to regular users
	 *
	 * @var bool
	 */
	protected $isSystem = false;

	/**
	 * This constructor will load a member group is passed an id
	 *
	 * @param null|int $id
	 */
	public function __construct($id = null)
	{
		if(!is_null($id))
			$this->loadMemberGroup($id);
	}

	/**
	 * Returns the id of the membergroup, or false if it hasn't been saved
	 *
	 * @return int|false
	 */
	public function getId()
	{
		return isset($this->id) ? $this->id : false;
	}

	/**
	 * Returns the name of the membergoup, or false if it hasn't been set
	 *
	 * @return string|false
	 */
	public function getName()
	{
		return isset($this->name) ? $this->name : false;
	}

	/**
	 * Sets the membergroup name
	 *
	 * @param string $name
	 * @return bool
	 */
	public function setName($name)
	{
		if(strlen($name) < 3)
			return false;

		$this->name = $name;
		return true;
	}

	/**
	 * Checks to see if a membergroup contains the specified user
	 *
	 * @cache models Users *userId membergroups *memberGroupId
	 * @param int $userId
	 * @return bool
	 */
	public function containsUser($userId)
	{
		//'models', 'Users', $userId, 'membergroups', $this->id
		$cache = new Cache('models', 'Users', $userId, 'membergroups', $this->id);
		$inGroup = $cache->getData();

		if($cache->isStale())
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE user_id = ? AND memgroup_id = ?');
			$stmt->bindAndExecute('ii', $userId, $this->id);

			$inGroup = ($stmt->num_rows == 1);
			$cache->storeData($inGroup);
		}

		return $inGroup;
	}

	/**
	 * This function returns an array of users that are in the membergroup.
	 *
	 * @cache membergroups *id userList *limit *offset
	 * @param int $limit Ignored if 0
	 * @param int $offset Ignored if a limit is not passed
	 * @return array Returns false if empty.
	 */
	public function getUsers($limit = 0, $offset = 0)
	{
		$cache = new Cache('membergroups', $this->getId(), 'userList', $limit, $offset);
		$results = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			if(!isset($limit) || $limit < 1)
			{
				$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE user_id = ? AND memgroup_id = ?');
				$stmt->bindAndExecute('ii', $userId, $this->id);
			}else{
				$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE user_id = ? AND memgroup_id = ? LIMIT ?,?');
				$stmt->bindAndExecute('iiii', $userId, $this->id, $offset, $limit);
			}
			$results = array();
			while($row = $stmt->fetch_array())
				$results[] = $row['user_id'];

			$cache->storeData($results);
		}

		return (count($results) > 0) ? $results : false;
	}

	/**
	 * Add a user to the membergoup
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function addUser($userId)
	{
		if(!$this->id)
			return false;

		if($userId instanceof Model && $userId->getType('User'))
			$userId = $userId->getId();

		if($this->containsUser($userId))
			return true;

		$dbWrite = db_connect('default');
		$insertStmt = $dbWrite->stmt_init();
		$insertStmt->prepare('INSERT INTO userInMemberGroup (user_id, memgroup_id) VALUES (?, ?)');
		$result = $insertStmt->bindAndExecute('ii', $userId, $this->id);
		Cache::clear('models', 'User', $userId, 'membergroups');
		return $result;
	}

	/**
	 * Remove a user from the membergroup
	 *
	 * @param int $user
	 * @return bool
	 */
	public function removeUser($user)
	{
		if(!$this->id)
			return false;

		if(!$this->containsUser($userId))
			return true;

		$dbWrite = db_connect('default');
		$deleteStmt = $dbWrite->stmt_init();
		$deleteStmt->prepare('DELETE FROM userInMemberGroup WHERE user_id = ? AND memgroup_id = ?');
		return $deleteStmt->bindAndExecute('ii', $userId, $this->id);
	}

	/**
	 * Check to see if a membergroup is reserved for internal user
	 *
	 * @return bool
	 */
	public function isSystem()
	{
		return ($this->isSystem);
	}

	/**
	 * Toggle a membergroup as internal or open
	 *
	 * @param bool $flag
	 */
	public function makeSystem($flag = true)
	{
		$this->isSystem = (bool) $flag;
	}

	/**
	 * Save the current membergroup
	 *
	 * @return bool
	 */
	public function save()
	{
		$dbWrite = DatabaseConnection::getConnection('default');
		if(!isset($this->id))
		{
			$insertStmt = $dbWrite->stmt_init();
			$insertStmt->prepare('INSERT INTO member_group (memgroup_name, is_system) VALUES (?, ?)');
			if($insertStmt->bindAndExecute('si', $this->name, ($this->isSystem ? 1 : 0)))
			{
				$this->id = $insertStmt->insert_id;
				return true;
			}else{
				return false;
			}
		}else{

			$insertStmt = $dbWrite->stmt_init();
			$insertStmt->prepare('UPDATE member_group SET memgroup_name = ? AND is_system = ? WHERE memgroup_id = ?');
			return $insertStmt->bindAndExecute('sii', $this->name, ($this->isSystem ? 1 : 0), $this->id);
		}
	}

	/**
	 * Loads the membergroup from the database or cache
	 *
	 * @access protected
	 * @cache membergroups *id
	 * @param int $id
	 * @return bool
	 */
	protected function loadMemberGroup($id)
	{
		$cache = new Cache('membergroups', $id);

		$info = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT memgroup_name, is_system FROM member_group WHERE memgroup_id = ?');
			$stmt->bindAndExecute('i', $id);

			if($stmt->num_rows == 1)
			{
				$row = $stmt->fetch_array();
				$info['name'] = $row['memgroup_name'];

				$info['isSystem'] = ($row['is_system'] == 1);
			}else{
				$info = false;
			}
			$cache->storeData($info);
		}

		if($info)
		{
			$this->id = $id;
			$this->name = $info['name'];
			$this->isSystem = $info['isSystem'];
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Returns the ID of a membergroup based on its name
	 *
	 * @cache membergroups lookup name *name id
	 * @static
	 * @param string $name
	 * @return int|false
	 */
	static public function lookupIdbyName($name)
	{
		$cache = new Cache('membergroups', 'lookup', 'name', $name, 'id');

		$id = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT memgroup_id FROM member_group WHERE memgroup_name = ?');
			$stmt->bindAndExecute('s', $name);

			if($stmt->num_rows == 1)
			{
				$results = $stmt->fetch_array();
				$id = $results['memgroup_id'];
			}else{
				$id = false;
			}
			$cache->storeData($id);
		}
		return $id;
	}
}

?>