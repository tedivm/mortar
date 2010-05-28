<?php

class TesseraComments
{
	static function canCommentModelType($resource)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		$cache = CacheControl::getCache('models', $resource, 'settings', 'comments');
		$data = $cache->getData();

		if($cache->isStale())
		{
			$stmt = DatabaseConnection::getStatement('default_read_only');
			$stmt->prepare('SELECT commentSetting FROM tesseraModelStatus WHERE modelId = ?');
			$stmt->bindAndExecute('i', $resource);

			if($row = $stmt->fetch_array())
			{
				$data = ($row['commentSetting'] == 1);
			}else{
				$data = false;
			}
			$cache->storeData($data);
		}

		return $data;
	}

	static function toggleCommentsForModel($resource, $enable = true)
	{
		if(!is_numeric($resource))
			$resource = ModelRegistry::getIdFromType($resource);

		if(!$resource)
			return false;

		$orm = new ObjectRelationshipMapper('tesseraModelStatus');
		$orm->modelId = $resource;
		$orm->select();
		$orm->commentSetting = ($enable) ? 1 : 0;

		$orm->save();
	}



}

?>