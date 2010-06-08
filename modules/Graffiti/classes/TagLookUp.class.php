<?php

class GraffitiTagLookUp
{
	static protected $stopWords;

	static function getTagId($tag, $add = true)
	{
		$tag = strtolower($tag);
		$tag = trim($tag);
		if($tag === '')
			return false;
		if(self::isStopWord($tag))
			return false;

		$cache = CacheControl::getCache('tags', 'tagid', $tag);
		$tagId = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT tagId FROM graffitiTags WHERE tag LIKE ?');
			$selectStmt->bindAndExecute('s', $tag);

			if($selectStmt->num_rows && $row = $selectStmt->fetch_array())
			{
				$tagId = $row['tagId'];
			} elseif($add) {
				$stem = GraffitiStemmer::stem($tag);
				$insertStmt = DatabaseConnection::getStatement('default');
				$insertStmt->prepare('INSERT INTO graffitiTags (tag, stem) VALUES (?, ?)');
				$insertStmt->bindAndExecute('ss', $tag, $stem);

				if(($id = $insertStmt->insert_id) && ($id > 0)) {
					$tagId = $insertStmt->insert_id;
					CacheControl::clearCache('tags', 'fromStem', $stem);
				} else {
					$tagId = false;
				}
			} else {
				$tagId = false;
			}
			$cache->storeData($tagId);
		}
		return $tagId;
	}

	static function getTagFromId($id)
	{
		$cache = CacheControl::getCache('tags', 'idtag', $id);
		$tag = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT tag FROM graffitiTags WHERE tagId = ?');
			$selectStmt->bindAndExecute('i', $id);

			if($selectStmt->num_rows && $row = $selectStmt->fetch_array())
			{
				$tag = $row['tag'];
			}else{
				$tag = false;
			}
			$cache->storeData($tag);
		}
		return $tag;
	}

	static function getTagsFromStem($stem)
	{
		$cache = CacheControl::getCache('tags', 'fromStem', $stem);
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT tagId FROM graffitiTags WHERE stem LIKE ?');
			$selectStmt->bindAndExecute('s', $stem);

			$tags = array();

			while($row = $selectStmt->fetch_array())
				$tags[] = $row['tagId'];

			if(!isset($tags[0]))
				$tags = false;

			$cache->storeData($tagId);
		}
		return $tags;
	}

	static function getTagsFromLocation(Location $location)
	{
		$locationId = $location->getId();
		$cache = CacheControl::getCache('locations', $locationId, 'tags', 'all');
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('	SELECT tagId, SUM(weight) AS tagWeight
						FROM graffitiLocationHasTags
						WHERE locationId = ?
						GROUP BY tagId');

			$selectStmt->bindAndExecute('i', $locationId);

			$tags = array();
			while($row = $selectStmt->fetch_array())
				$tags[$row['tagId']] = $row['tagWeight'];

			$cache->storeData($tags);
		}
		return $tags;
	}

	static function getUserTags(Location $location, Model $user)
	{
		$locationId = $location->getId();
		$userId = $user->getId();
		$cache = CacheControl::getCache('locations', $locationId, 'tags', 'user', $userId);
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('	SELECT tagId
						FROM graffitiLocationHasTags
						WHERE locationId = ?
						AND userId = ?');

			$selectStmt->bindAndExecute('ii', $locationId, $userId);

			$tags = array();
			while($row = $selectStmt->fetch_array())
				$tags[] = $row['tagId'];

			$cache->storeData($tags);
		}
		return $tags;

	
	}

	static function getLocationsForTag($tag, $owner = false)
	{
		if(!is_numeric($tag)) {
			$tag = self::getTagId($tag, false);
		}

		if($tag === false)
			return false;

		$cache = CacheControl::getCache('tags', $tag, 'locations', 'owner', (bool) $owner);
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			if(!$owner) {
				$selectStmt->prepare('	SELECT locationId
							FROM graffitiLocationHasTags
							WHERE tagId = ?');
			} else {
				$selectStmt->prepare('	SELECT locationId
							FROM graffitiLocationHasTags
							INNER JOIN locations
							ON graffitiLocationHasTags.locationId = locations.location_id
							AND graffitiLocationHasTags.userId = locations.owner
							WHERE tagId = ?');
			}

			$selectStmt->bindAndExecute('i', $tag);
			$locs = array();
			while($row = $selectStmt->fetch_array())
				$locs[] = $row['locationId'];

			$cache->storeData($locs);
		}

		return $locs;
	}

	static function getTagList()
	{
		$cache = CacheControl::getCache('tags', 'list', 'all');
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$db = DatabaseConnection::getConnection('default_read_only');
			$result = $db->query(  'SELECT tag, sum(weight) as tagWeight
						FROM graffitiTags
						INNER JOIN graffitiLocationHasTags
						ON graffitiTags.tagId = graffitiLocationHasTags.tagId
						GROUP BY graffitiTags.tagId
						ORDER BY tag');

			$tags = array();
			while($row = $result->fetch_array())
				$tags[] = array('tag' => $row['tag'], 'weight' => $row['tagWeight']);

			$cache->storeData($tags);
		}

		return $tags;
	}

	static function isStopWord($word)
	{
		if($word == 'us')
			return true;

		if(!isset(self::$stopWords))
		{
			$packageInfo = new PackageInfo('Graffiti');
			$pathToStopWords = $packageInfo->getPath() . 'data/stopwords';

			if(file_exists($pathToStopWords))
			{
				self::$stopWords = file($pathToStopWords);
			}else{
				self::$stopWords = array();
			}
		}

		return in_array($word, self::$stopWords);
	}
}

?>