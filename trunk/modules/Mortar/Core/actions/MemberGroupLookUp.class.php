<?php

class MortarCoreActionMemberGroupLookUp extends ActionBase
{
	static $requiredPermission = 'Read';

	protected $list = array();

	protected $maxLimit = 25;
	protected $limit = 10;

	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$query = Query::getQuery();
		if(isset($query['term']) && ActiveUser::isLoggedIn())
		{
			$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

			if($limit > $this->maxLimit)
				$limit = $this->maxLimit;

			$cache = CacheControl::getCache('MemberGroupLookUp', 'bystring', $query['term'], $limit);
			$memberGroupList = $cache->getData();

			if($cache->isStale())
			{
				$memberGroupList = array();
				$searchString = isset($query['term']) ? '%' . $query['term'] . '%' : '%';

				$stmt = DatabaseConnection::getStatement('default_read_only');
				$stmt->prepare('SELECT memgroup_id, memgroup_name
									FROM memberGroup
									WHERE memgroup_name	LIKE ?
										AND is_system = 0
									LIMIT ?');
				$stmt->bindAndExecute('si', $searchString, $limit);

				while($results = $stmt->fetch_array())
					$memberGroupList[] = array(	'value' => $results['memgroup_name'], 
									'label' => $results['memgroup_name'],
									'id' => $results['memgroup_id']);

				$cache->storeData($memberGroupList);
			}
			$this->list = $memberGroupList;

		}else{

		}
	}

	public function viewAdmin($page)
	{
		$output = '';
		foreach($this->list as $memberGroup)
			$output .= $memberGroup['id'] . ': ' . $memberGroup['value'] . '<br>';
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