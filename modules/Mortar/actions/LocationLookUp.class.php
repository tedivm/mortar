<?php

class MortarActionLocationLookUp extends ActionBase
{
	protected $list = array();

	protected $maxLimit = 25;
	protected $limit = 10;

	public function logic()
	{
		$offset = 43200;
		$this->ioHandler->addHeader('Expires', gmdate(HTTP_DATE, time() + $offset));

		$query = Query::getQuery();
		if(isset($query['q']) && ActiveUser::isLoggedIn()) {

			$limit = isset($query['limit']) && is_numeric($query['limit']) ? $query['limit'] : $this->limit;

			if($limit > $this->maxLimit)
				$limit = $this->maxLimit;

			$cache = CacheControl::getCache('locationLookup', 'bystring', $query['q'], $limit);
			$locList = $cache->getData();

			if($cache->isStale())
			{
				if(isset($query['s']) && is_numeric($query['s'])) {
					$prefix = Location::getPathById($query['s']);
				}

				$path = explode('/', $query['q']);

				if(count($path) === 1) {
					$parent = 1;
					$q = $query['q'];
				} else {
					if(isset($prefix)) {
						$parentPath = $prefix . '/';
					} else {
						$parentPath = '';
					}

					foreach($path as $num => $loc) {
						if(($num + 1) < count($path)) {
							$parentPath .= $loc . '/';
						} else {
							$q = $loc;
						}
					}

					$id = Location::getIdByPath($parentPath);
					if($id) {
						$parent = $id;
					}
				}

				$locList = array();

				if(isset($parent)) {
					$searchString = '%' . $q . '%';

					$stmt = DatabaseConnection::getStatement('default_read_only');

					$stmt->prepare('SELECT location_id, name
							FROM locations
							WHERE name LIKE ?
							AND parent = ?
							ORDER BY name ASC
							LIMIT ?');

					$stmt->bindAndExecute('sii', $searchString, $parent, $limit);

					while($results = $stmt->fetch_array()) {
						if(isset($query['s']) && is_numeric($query['s'])) {
							$base = $query['s'];
						} else {
							$base = 1;
						}

						$name = Location::getPathById($results['location_id'], $base);
						$locList[] = array('name' => $name, 'id' => $results['location_id']);
					}
				}

				$cache->storeData($locList);
			}
			$this->list = $locList;

		}else{

		}
	}

	public function viewAdmin($page)
	{
		$output = '';
		foreach($this->list as $loc)
			$output .= $loc['id'] . ': ' . $loc['name'] . '<br>';
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