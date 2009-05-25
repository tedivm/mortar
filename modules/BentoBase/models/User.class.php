<?php

class BentoBaseModelUser extends AbstractModel
{
	static public $type = 'User';
	protected $table = 'users';

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

	protected function load($id)
	{
		if(parent::load($id))
		{
			$cache = new Cache('models', $this->getType(), $id, 'membergroups');
			$memberGroups = $cache->getData();
			if(!$cache->cacheReturned)
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