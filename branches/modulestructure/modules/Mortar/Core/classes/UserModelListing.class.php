<?php

class MortarCoreUserModelListing extends ModelListing
{

	protected function filterModels($modelArray)
	{
		$modelArray = parent::filterModels($modelArray);

		foreach($modelArray as $userInfo) {
			$user = ModelRegistry::loadModel('User', $userInfo['id']);
			$system = true;
			if(!isset($user['membergroups'])) {
				$system = false;
			} else {
				foreach($user['membergroups'] as $id) {
					$group = ModelRegistry::loadModel('MemberGroup', $id);
					if($group['is_system'] === '0') {
						$system = false;
						break;
					}
				}
			}

			if (!$system)
				$models[] = $userInfo;
		}

		return $models;

	}

}

?>