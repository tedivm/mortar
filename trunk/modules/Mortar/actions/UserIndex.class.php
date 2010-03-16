<?php

class MortarActionUserIndex extends ModelActionIndex
{

	public function logic()
	{
		parent::logic();

		$models = array();
		foreach($this->childModels as $user) {
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
				$models[] = $user;
		}

		$this->childModels = $models;
	}

}

?>