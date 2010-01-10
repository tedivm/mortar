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
class MortarModelMemberGroup extends ModelBase
{
	static public $type = 'MemberGroup';
	protected $table = 'memberGroup';

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
				$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE memgroup_id = ?');
				$stmt->bindAndExecute('i', $this->id);
			}else{
				$stmt->prepare('SELECT user_id FROM userInMemberGroup WHERE memgroup_id = ? LIMIT ?,?');
				$stmt->bindAndExecute('iii', $this->id, $offset, $limit);
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
	 * Loads the membergroup whose name is provided into this model
	 *
	 * @cache membergroups lookup name *name id
	 * @static
	 * @param string $name
	 * @return int|false
	 */
	public function loadbyName($name)
	{
		$cache = new Cache('membergroups', 'lookup', 'name', $name, 'id');

		$id = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT memgroup_id FROM memberGroup WHERE memgroup_name = ?');
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
		return $this->load($id);
	}

	public function offsetGet($offset)
	{
		if($offset === 'name')
			return $this->content['memgroup_name'];
		
		return parent::offsetGet($offset);
	}

	public function offsetSet($offset, $value)
	{
		if($offset === 'name')
			$this->content['memgroup_name'] = $value;
		else
			return parent::offsetSet($offset, $value);
	}

	public function offsetExists($offset)
	{
		if($offset === 'name')
			$offset = 'memgroup_name';
		
		return parent::offsetExists($offset);
	}

        public function offsetUnset($offset)
        {
		if($offset === 'name')
			unset($this->content['memgroup_name']);

		return parent::offsetUnset($offset);
        }

}

?>