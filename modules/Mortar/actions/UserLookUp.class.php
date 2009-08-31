<?php

class MortarActionUserLookUp extends ActionBase
{
	protected $list;

	public function logic()
	{
		$query = Query::getQuery();
		if(isset($query['q'])
			&& strlen($query['q']) > 2
			&& ActiveUser::isLoggedIn())
		{
			if(isset($query['m']))
			{
				if(!is_numeric($query['m']))
				{
					$membergroup = $query['m'];
				}else{
					$membergroup = ($membergroupId = MemberGroup::lookupIdbyName($query['m'])) ? $membergroupId : 'all';
				}
			}else{
				$membergroup = 'all';
			}

			$cache = new Cache('userLookup', 'bystring', $membergroup, $query['q']);
			$userList = $cache->getData();

			if($cache->isStale())
			{
				$userList = array();
				$searchString = '%' . $query['q'] . '%';
				$stmt = DatabaseConnection::getStatement('default_read_only');

				if(is_numeric($membergroup))
				{
					$stmt->prepare('SELECT users.user_id, name
									FROM users JOIN userInMemberGroup
										ON users.user_id = userInMemberGroup.user_id
									WHERE name LIKE ?
										memgroup_id = ?');
					$stmt->bindAndExecute('si', $searchString, $membergroup);
				}else{
					$stmt->prepare('SELECT user_id, name FROM users WHERE name LIKE ?');
					$stmt->bindAndExecute('s', $searchString);
				}

				while($results = $stmt->fetch_array())
					$userList[] = array('name' => $results['name'], 'id' => $results['user_id']);

				$cache->storeData($userList);
			}
			$this->list = $userList;

		}else{

		}
	}

	public function viewAdmin()
	{
		$output = '';
		foreach($this->list as $user)
			$output .= $user['id'] . ': ' . $user['name'] . '<br>';
		return $output;
	}

	public function viewHtml()
	{
		return $html;
	}

	public function viewXml()
	{
		return $xml;
	}

	public function viewJson()
	{
		return $this->list;
	}



}

?>