<?php

class MortarMemberGroupModelListing extends ModelListing
{

	protected function filterModels($modelArray)
	{
		$modelArray = parent::filterModels($modelArray);

		$models = array();

		foreach($modelArray as $groupInfo) {
			$group = ModelRegistry::loadModel('MemberGroup', $groupInfo['id']);
			if (!$group['is_system'])
				$models[] = $groupInfo;
		}

		return $models;

	}

}

?>