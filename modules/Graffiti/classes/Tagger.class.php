<?php

class GraffitiTagger
{
	static function tagLocation($tags, Location $location, Model $user, $weight = 1)
	{
		if(!isset($tags))
			throw new TypeMismatch(array('Array or String', $tags));

		if(!is_array($tags))
			$tags = array($tags);

		$locationId = $location->getId();
		$userId = $user->getId();

		if(!is_numeric($weight))
			throw new TypeMismatch(array('Integer', $weight));

		foreach($tags as $tag)
		{
			if(is_numeric($tag))
			{
				$tagId = $tag;
			}else{
				if(!($tagId = GraffitiTagLookUp::getTagId($tag)))
					continue;
			}

			$insertStatement = DatabaseConnection::getStatement('default');
			$insertStatement->prepare('	INSERT IGNORE
							INTO graffitiLocationHasTags
								(tagId, locationId, userId, weight, createdOn)
							VALUES (?, ?, ?, ?, NOW())');
			$insertStatement->bindAndExecute('iiii', $tagId, $locationId, $userId, $weight);


		}//foreach($tags as $tag)
	}

	static function clearTagsFromLocation(Location $location, Model $user = null)
	{
		$locationId = $location->getId();
		$deleteStatement = DatabaseConnection::getStatement('default');

		if(isset($user) && $userId = $user->getId())
		{
			$deleteStatement->prepare('	DELETE
							FROM graffitiLocationHasTags
							WHERE locationId = ? AND userId = ?');
			$deleteStatement->bindAndExecute('ii', $locationId, $userId);
		}else{
			$deleteStatement->prepare('	DELETE
							FROM graffitiLocationHasTags
							WHERE locationId = ?');
			$deleteStatement->bindAndExecute('i', $locationId);
		}

		CacheControl::clearCache('locations', $locationId, 'tags');
	}

	static function canTagModelType($resource)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		$cache = CacheControl::getCache('models', $resource, 'settings', 'tagging');
		$data = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT tagSetting FROM graffitiModelStatus WHERE modelId = ?');
			$stmt->bindAndExecute('i', $resource);

			if($row = $stmt->fetch_array())
			{
				$data = ($row['tagSetting'] == 1);
			}else{
				$data = false;
			}
			$cache->storeData($data);
		}

		return $data;
	}

	static function toggleTaggingForModel($resource, $enable = true)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!$resource)
			return false;

		$orm = new ObjectRelationshipMapper('graffitiModelStatus');
		$orm->modelId = $resource;
		$orm->select();
		$orm->tagSetting = ($enable) ? 1 : 0;

		$orm->save();
	}
}

?>