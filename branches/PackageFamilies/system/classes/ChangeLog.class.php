<?php

class ChangeLog
{
	static function getChanges($model)
	{
		if(!$modelId = $model->getId())
			return false;

		$type = $model->getType();
		$typeId = ModelRegistry::getIdFromType($type);

		$cache = CacheControl::getCache('change', 'model', $type, $modelId);
		$changeData = $cache->getData();

		if($cache->isStale())
		{
			$changeData = array();

			$sql = 'SELECT changeTypeText as `change`, changeDate as `date`, 
					action_name AS `permission`, changeUser as `user`, note
				FROM changeLog
				INNER JOIN changeTypes ON changeLog.changeType = changeTypes.changeTypeId
				LEFT JOIN actions ON changeLog.permission = actions.action_id
				WHERE modelType = ? AND modelId = ?';

			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare($sql);
			$selectStmt->bindAndExecute('ii', $typeId, $modelId);

			while($row = $selectStmt->fetch_array()) {
				$changeData[] = $row;
			} 
			$cache->storeData($changeData);
		}
		return $changeData;
	}

	static function logChange($model, $change, $user = null, $permission = null, $note = null)
	{
		if(!$modelId = $model->getId())
			return false;

		$type = $model->getType();
		$typeId = ModelRegistry::getIdFromType($type);
		$date = gmdate('Y-m-d H:i:s');
		$changeId = self::getChangeId($change);

		$bindFields = 'modelType, modelId, changeType, changeDate';
		$bindValues = array('iiis', $typeId, $modelId, $changeId, $date);
		$bindQs = '?, ?, ?, ?';

		if(isset($permission)) {
			if(!is_numeric($permission)) {
				$permission = PermissionActionList::getAction($permission);
			}

			if($permission) {
				$bindFields .= ', permission';
				$bindValues[0] .= 'i';
				$bindQs .= ', ?';
				$bindValues[] = $permission;
			}
		}

		if(isset($user)) {
			if($user instanceof MortarModelUser) {
				$user = $user->getId();
			}

			if($user) {
				$bindFields .= ', changeUser';
				$bindValues[0] .= 'i';
				$bindQs .= ', ?';
				$bindValues[] = $user;
			}
		}

		if(isset($note)) {
			$bindFields .= ', note';
			$bindValues[0] .= 's';
			$bindQs .= ', ?';
			$bindValues[] = $note;			
		}

		$insertStmt = DatabaseConnection::getStatement('default');

		$sql  = 'INSERT INTO changeLog ';
		$sql .= '(' . $bindFields . ') ';
		$sql .= 'VALUES (' . $bindQs . ')';

		$insertStmt->prepare($sql);

		call_user_func_array(array($insertStmt, 'bindAndExecute'), $bindValues);

		if(($id = $insertStmt->insert_id) && ($id > 0)) {
			CacheControl::clearCache('change', 'model', $type, $modelId);
			return true;
		} else {
			return false;
		}
	}

	static function getChangeId($change)
	{
		$change = trim($change);
		if($change === '')
			return false;

		$cache = CacheControl::getCache('change', 'changeid', $change);
		$changeId = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT changeTypeId FROM changeTypes WHERE changeTypeText LIKE ?');
			$selectStmt->bindAndExecute('s', $change);

			if($selectStmt->num_rows && $row = $selectStmt->fetch_array())
			{
				$changeId = $row['changeTypeId'];
			} else {
				$insertStmt = DatabaseConnection::getStatement('default');
				$insertStmt->prepare('INSERT INTO changeTypes (changeTypeText) VALUES (?)');
				$insertStmt->bindAndExecute('s', $change);

				if(($id = $insertStmt->insert_id) && ($id > 0)) {
					$changeId = $insertStmt->insert_id;
				} else {
					$changeId = false;
				}
			} 
			$cache->storeData($changeId);
		}
		return $changeId;
	}

	static function getChangeFromId($id)
	{
		$cache = CacheControl::getCache('change', 'idchange', $id);
		$tag = $cache->getData();

		if($cache->isStale())
		{
			$selectStmt = DatabaseConnection::getStatement('default_read_only');
			$selectStmt->prepare('SELECT changeTypeText FROM changeTypes WHERE changeTypeId = ?');
			$selectStmt->bindAndExecute('i', $id);

			if($selectStmt->num_rows && $row = $selectStmt->fetch_array())
			{
				$change = $row['changeTypeText'];
			}else{
				$change = false;
			}
			$cache->storeData($change);
		}
		return $change;
	}
}

?>