<?php

class MortarActionUserLookUp extends ActionBase
{
	protected $list;

	protected $maxLimit = 25;
	protected $Limit = 10;

	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$query = Query::getQuery();
		if(isset($query['q'])
			&& ActiveUser::isLoggedIn())
		{
			if(isset($query['m']))
			{
				if(is_numeric($query['m']))
				{
					$membergroup = $query['m'];
				}else{
					$mg = ModelRegistry::loadModel('MemberGroup');
					$mgLoaded = $mg->loadByName($query['m']);
					$membergroup = ($mgLoaded) ? $mg->getId() : 'all';
				}
			}else{
				$membergroup = 'all';
			}

			$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

			if($limit > $this->maxLimit)
				$limit = $this->maxLimit;

			$cache = new Cache('userLookup', 'bystring', $membergroup, $query['q'], $limit);
			$userList = $cache->getData();

			if($cache->isStale())
			{
				$userList = array();
				$searchString = isset($query['q']) ? '%' . $query['q'] . '%' : '%';

				$stmt = DatabaseConnection::getStatement('default_read_only');

				if(is_numeric($membergroup))
				{
					$stmt->prepare('SELECT users.user_id, name
									FROM users JOIN userInMemberGroup
										ON users.user_id = userInMemberGroup.user_id
									WHERE name LIKE ? AND
										memgroup_id = ?
									ORDER BY name ASC
									LIMIT ?');
					$stmt->bindAndExecute('sii', $searchString, $membergroup, $limit);
				}else{
					$stmt->prepare('SELECT user_id, name FROM users WHERE name LIKE ? LIMIT ?');
					$stmt->bindAndExecute('si', $searchString, $limit);
				}

				while($results = $stmt->fetch_array())
					$userList[] = array('name' => $results['name'], 'id' => $results['user_id']);

				$cache->storeData($userList);
			}
			$this->list = $userList;

		}else{

		}
	}

	public function viewAdmin($page)
	{
		$output = '';
		foreach($this->list as $user)
			$output .= $user['id'] . ': ' . $user['name'] . '<br>';
		return $output;
	}

	public function viewHtml($page)
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