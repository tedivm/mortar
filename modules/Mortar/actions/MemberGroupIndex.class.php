<?php

class MortarActionMemberGroupIndex extends ModelActionIndex
{

	public function logic()
	{
		parent::logic();

		$models = array();
		foreach($this->childModels as $group) {
			if (!$group['is_system'])
				$models[] = $group;
		}

		$this->childModels = $models;
	}

}

?>