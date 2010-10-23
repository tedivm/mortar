<?php

class GraffitiActionTagList extends ActionBase
{
	protected $list = array();

	protected $maxLimit = 25;
	protected $limit = 10;


	public function logic()
	{
		$query = Query::getQuery();
		$searchString = isset($query['term']) ? $query['term'] . '%' : '%';
		$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

		if($limit > $this->maxLimit)
			$limit = $this->maxLimit;

		$type = (isset($query['type'])) ? $query['type'] : 'all';
		$cache = CacheControl::getCache('tags', 'lookup', $type, $searchString, $limit);

		$tagList = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement();

			if($type != 'all')
			{
				$stmt->prepare('SELECT DISTINCT tag, tagId, SUM(weight) AS tagWeight
								FROM graffitiTags, graffitiLocationHasTags, locations
								WHERE
									locations.resourceType = ?
								AND
									graffitiLocationHasTags.locationId = locations.location_id
								AND
									graffitiLocationHasTags.tagId = graffitiTags.tagId
								AND
									tag LIKE ?
								ORDER BY tagWeight ASC
								GROUP BY tagId
								LIMIT ?');
				$stmt->bindAndExecute('ssi', $resourceType, $searchString, $limit);
			}else{
				$stmt->prepare('SELECT tag FROM graffitiTags WHERE tag LIKE ? LIMIT ?');
				$stmt->bindAndExecute('si', $searchString, $limit);
			}

			while($results = $stmt->fetch_array())
				$tagList[] = array(	'value' => $results['tag'],
							'id' => $results['tag'],
							'label' => $results['tag']);

			$cache->storeData($tagList);
		}

		$this->list = $tagList;
	}

	public function viewAdmin($page)
	{
		$output = '';
		if(count($this->list)) foreach($this->list as $tag)
			$output .= $tag['value'] . '<br>';
		return $output;
	}

	public function viewJson()
	{
		return $this->list;
	}
}

?>