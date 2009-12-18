<?php

class MortarActionMemberGroupLookUp extends ActionBase
{
	protected $list;

	protected $maxLimit = 25;
	protected $Limit = 10;

	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$query = Query::getQuery();
		if(isset($query['q']) && ActiveUser::isLoggedIn())
		{
			$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

			if($limit > $this->maxLimit)
				$limit = $this->maxLimit;

			$cache = new Cache('MemberGroupLookUp', 'bystring', $query['q'], $limit);
			$memberGroupList = $cache->getData();

			if($cache->isStale())
			{
				$memberGroupList = array();
				$searchString = isset($query['q']) ? '%' . $query['q'] . '%' : '%';

				$stmt = DatabaseConnection::getStatement('default_read_only');
				$stmt->prepare('SELECT memgroup_id, memgroup_name
									FROM memberGroup
									WHERE memgroup_name	LIKE ?
										AND is_system = 0
									LIMIT ?');
				$stmt->bindAndExecute('si', $searchString, $limit);

				while($results = $stmt->fetch_array())
					$memberGroupList[] = array('name' => $results['memgroup_name'], 'id' => $results['memgroup_id']);

				$cache->storeData($memberGroupList);
			}
			$this->list = $memberGroupList;

		}else{

		}
	}

	public function viewAdmin()
	{
		$output = '';
		foreach($this->list as $memberGroup)
			$output .= $memberGroup['id'] . ': ' . $memberGroup['name'] . '<br>';
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