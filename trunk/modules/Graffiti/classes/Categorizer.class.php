<?php

class GraffitiCategorizer
{
	static function getCategoryTree()
	{
		$cache = CacheControl::getCache('models', 'Category', 'getCategoryTree');
		$desc = $cache->getData();

		if($cache->isStale()) {
			$db = DatabaseConnection::getConnection('default_read_only');
			$results = $db->query('	SELECT categoryId, name
						FROM graffitiCategories
						WHERE parent IS NULL
						ORDER BY name');
			$cats = array();

			while($row = $results->fetch_array()) {
				$item = array();
				$model = ModelRegistry::loadModel('Category', $row['categoryId']);
				$item['id'] = $row['categoryId'];
				$item['name'] = $row['name'];
				$item['children'] = $model->getDescendants();

				$cats[] = $item;
			}
			$cache->storeData($cats);
		}

		return $cats;
	}

	static function getDisplayTree()
	{
		$cats = self::getCategoryTree();
		return self::processTreeLevel($cats, 0);
	}

	static function processTreeLevel($cats, $level = 0)
	{
		$display = array();

		if(is_array($cats) && count($cats) === 0)
			return array();

		foreach($cats as $cat) {
			$item = array();
			$item['name'] = $cat['name'];
			$item['id'] = $cat['id'];
			$item['level'] = $level;
			$display[] = $item;

			$children = self::processTreeLevel($cat['children'], $level + 1);

			foreach($children as $item)
				$display[] = $item;
		}

		return $display;
	}

	static function categorizeLocation($loc, $cat, $has = true)
	{
		if($loc instanceof Location)
			$loc = $loc->getId();

		if(!is_numeric($loc))
			return false;

		if(method_exists($cat, 'getId'))
			$cat = $cat->getId();

		if(!is_numeric($cat))
			return false;

		$stmt = DatabaseConnection::getStatement('default');

		if(!$has) {
			$stmt->prepare('DELETE FROM graffitiLocationCategories
					WHERE categoryId = ?
					AND locationId = ?');
		} else {
			$stmt->prepare('INSERT IGNORE
					INTO graffitiLocationCategories
						(categoryId, locationId)
					VALUES (?, ?)');
		}

		$stmt->bindAndExecute('ii', $cat, $loc);

		CacheControl::clearCache('models', 'Category');
	}

	static function isLocationInCategory($loc, $cat)
	{
		if($loc instanceof Location)
			$loc = $loc->getId();

		if(!is_numeric($loc))
			return false;

		if(method_exists($cat, 'getId'))
			$cat = $cat->getId();

		if(!is_numeric($cat))
			return false;

		$cats = self::getLocationCategories($loc);

		if(in_array($cat, $cats))
			return true;

		$model = ModelRegistry::loadModel('Category', $cat);

		foreach($cats as $check) {
			if($model->hasAncestor($check)) {
				return true;
			}
		}

		return false;
	}

	static function getLocationCategories($loc, $hier = false)
	{
		if($loc instanceof Location)
			$loc = $loc->getId();

		if(!is_numeric($loc))
			return false;

		$cache = CacheControl::getCache('models', 'Category', 'locations', $loc, 'getLocationCategories', $hier);
		$desc = $cache->getData();

		if($cache->isStale()) {
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT categoryId
					FROM graffitiLocationCategories
					WHERE locationId = ?');
			$stmt->bindAndExecute('i', $loc);

			$cats = array();

			while($row = $stmt->fetch_array()) {
				$cats[] = $row['categoryId'];
			}

			if($hier) {
				$allcats = $cats;
				foreach($cats as $cat) {
					$model = ModelRegistry::loadModel('Category', $cat);
					while($parent = $model->getParent()) {
						if(in_array($parent->getId(), $allcats))
							break;

						$allcats[] = $parent->getId();
						$model = $parent;
					}
				}

				$cats = $allcats;
			}
			$cache->storeData($cats);
		}

		return $cats;
	}

	static function getCategoryLocations($cat)
	{
		if(method_exists($cat, 'getId'))
			$cat = $cat->getId();

		if(!is_numeric($cat))
			return false;

		$cache = CacheControl::getCache('models', 'Category', $cat, 'getCategoryLocations');
		$desc = $cache->getData();

		if($cache->isStale()) {
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT locationId
					FROM graffitiLocationCategories
					WHERE categoryId = ?');
			$stmt->bindAndExecute('i', $cat);

			$locs = array();

			while($row = $stmt->fetch_array()) {
				$item = array();
				$loc = Location::getLocation($row['locationId']);
				$model = $loc->getResource();

				$item['id'] = $row['locationId'];
				$item['name'] = isset($model['title']) ? $model['title'] : $model->getName();
				$item['url'] = (string) $model->getUrl();

				$locs[] = $item;
			}
			$cache->storeData($locs);
		}
		return $locs;
	}

	static function canCategorizeModelType($resource)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		$cache = CacheControl::getCache('models', $resource, 'settings', 'categories');
		$data = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT categorySetting FROM graffitiModelStatus WHERE modelId = ?');
			$stmt->bindAndExecute('i', $resource);

			if($row = $stmt->fetch_array())
			{
				$data = ($row['categorySetting'] == 1);
			}else{
				$data = false;
			}
			$cache->storeData($data);
		}

		return $data;
	}

	static function toggleCategoriesForModel($resource, $enable = true)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!$resource)
			return false;

		$orm = new ObjectRelationshipMapper('graffitiModelStatus');
		$orm->modelId = $resource;
		$orm->select();
		$orm->categorySetting = ($enable) ? 1 : 0;

		$orm->save();
	}

}

?>