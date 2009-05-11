<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 */

/**
 * This object represents a group of users
 *
 * @package MainClasses
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
	 * @param int $userId
	 * @return bool
	 */
	public function containsUser($userId)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE user_id = ? AND memgroup_id = ?');
		$stmt->bindAndExecute('ii', $userId, $this->id);
		return ($stmt->num_rows == 1);
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

		if($userId instanceof User)
			$userId = $userId->getId();

		if($this->containsUser($userId))
			return true;

		$dbWrite = db_connect('default');
		$insertStmt = $dbWrite->stmt_init();
		$insertStmt->prepare('INSERT INTO userInMemberGroup (user_id, memgroup_id) VALUES (?, ?)');
		return $insertStmt->bindAndExecute('ii', $userId, $this->id);
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
		$this->isSystem = bool ($flag);
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
	 * @param int $id
	 * @return bool
	 */
	protected function loadMemberGroup($id)
	{
		$cache = new Cache('membergroups', $id);

		$info = $cache->getData();

		if(!$cache->cacheReturned)
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
	 * @static
	 * @param string $name
	 * @return int|false
	 */
	static public function lookupIdbyName($name)
	{
		$cache = new Cache('membergroups', 'lookup', 'name', $name, 'id');

		$id = $cache->getData();

		if(!$cache->cacheReturned)
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