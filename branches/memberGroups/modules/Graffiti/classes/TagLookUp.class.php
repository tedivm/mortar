<?php

class GraffitiTagLookUp
{
	static protected $stopWords;

	static function getTagId($tag)
	{
		$tag = strtolower($tag);
		$tag = trim($tag);
		if(self::isStopWord($tag))
			return false;

		$cache = new Cache('tags', 'tagid', $tag);
		$tagId = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT tagId FROM graffitiTags WHERE tag LIKE ?');
			$selectStmt->bindAndExecute('s', $tag);

			if($selectStmt->num_rows && $row = $selectStmt->fetch_array())
			{
				$tagId = $row['tagId'];
			}else{
				$stem = GraffitiStemmer::stem($tag);
				$insertStmt = DatabaseConnection::getStatement('default');
				$insertStmt->prepare('INSERT INTO graffitiTags (tag, stem) VALUES (?, ?)');
				$insertStmt->bindAndExecute('ss', $tag, $stem);

				if(isset($insertStmt->insert_id) && $insertStmt->insert_id > 0)
				{
					$tagId = $insertStmt->insert_id;
					Cache::clear('tags', 'fromStem', $stem);
				}else{
					$tagId = false;
				}
			}
			$cache->storeData($tagId);
		}
		return $tagId;
	}

	static function getTagFromId($id)
	{
		$cache = new Cache('tags', 'idtag', $id);
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
		return $tagId;
	}

	static function getTagsFromStem($stem)
	{
		$cache = new Cache('tags', 'fromStem', $stem);
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
		$cache = new Cache('locations', $locationId, 'tags');
		$tags = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT tagId, SUM(weight) AS tagWeight
							FROM graffitiLocationHasTags
							WHERE locationId LIKE ?
							GROUP BY tagId');

			$selectStmt->bindAndExecute('s', $stem);

			$tags = array();
			while($row = $selectStmt->fetch_array())
				$tags[$row['tagId']] = $row['tagWeight'];

			$cache->storeData($tagId);
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