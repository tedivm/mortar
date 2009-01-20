<?php

class MemberGroup
{
	protected $name;
	protected $id = false;

	public function __construct($id = null)
	{
		if(!is_null($id))
			$this->loadMemberGroup($id);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getName($name)
	{
		return $this->name;
	}

	public function setName($name)
	{
		if(strlen($name) < 3)
			return false;

		$this->name = $name;

	}

	public function containsUser($userId)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare('SELECT user_id FROM user_in_member_group WHERE user_id = ? AND memgroup_id = ?');
		$stmt->bind_param_and_execute('ii', $userId, $this->id);
		return ($stmt->num_rows == 1);
	}

	public function addUser($userId)
	{
		if(!$this->id)
			return false;

		if($this->containsUser($userId))
			return true;

		$dbWrite = db_connect('default');
		$insertStmt = $dbWrite->stmt_init();
		$insertStmt->prepare('INSERT INTO user_in_member_group (user_id, memgroup_id) VALUES (?, ?)');
		return $insertStmt->bind_param_and_execute('ii', $userId, $this->id);
	}

	public function removeUser($user)
	{
		if(!$this->id)
			return false;

		if(!$this->containsUser($userId))
			return true;

		$dbWrite = db_connect('default');
		$deleteStmt = $dbWrite->stmt_init();
		$deleteStmt->prepare('DELETE FROM user_in_member_group WHERE user_id = ? AND memgroup_id = ?');
		return $deleteStmt->bind_param_and_execute('ii', $userId, $this->id);
	}

	public function save()
	{
		$dbWrite = db_connect('default');
		if(!$this->id)
		{
			$insertStmt = $dbWrite->stmt_init();
			$insertStmt->prepare('INSERT INTO member_group (memgroup_name) VALUES (?)');
			if($insertStmt->bind_param_and_execute('s', $this->name))
			{
				$this->id = $insertStmt->insert_id;
				return true;
			}else{
				return false;
			}
		}else{

			$insertStmt = $dbWrite->stmt_init();
			$insertStmt->prepare('UPDATE member_group SET memgroup_name = ? WHERE memgroup_id = ?');
			return $insertStmt->bind_param_and_execute('si', $this->name, $this->id);
		}
	}


	protected function loadMemberGroup($id)
	{
		$cache = new Cache('membergroups', $id);

		$info = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = dbConnect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT memgroup_name FROM member_group WHERE memgroup_id = ?');
			$stmt->bind_param_and_execute('i', $id);

			if($stmt->num_rows == 1)
			{
				$row = $stmt->fetch_array();
				$info['name'] = $row['memgroup_name'];
			}else{
				$info = false;
			}
			$cache->storeData($info);
		}

		if($info)
		{
			$this->id = $id;
			$this->name = $info['name'];
			return true;
		}else{
			return false;
		}
	}

	static public function lookupIdbyName($name)
	{
		$cache = new Cache('membergroups', 'lookup', 'name', $name, 'id');

		$id = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = dbConnect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT memgroup_id FROM member_group WHERE memgroup_name = ?');
			$stmt->bind_param_and_execute('s', $name);

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