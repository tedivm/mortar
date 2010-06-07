<?php

class MortarUserForm extends ModelForm
{

	protected function createCustomInputs()
	{
		$this->changeSection('Info');
		$this->setLegend('Member Information');

		$this->createInput('model_name')->
			setLabel('Display/Login Name')->
			addRule('required')->
			addRule('maxlength', 40);

		$this->createInput('model_email')->
			setLabel('Email')->
			addRule('email');

		$this->createInput('model_allowlogin')->
			setType('checkbox')->
			setLabel('Allow Login');

		$this->createInput('password')->
			setType('password')->
			setLabel('Password');

		$this->createInput('password_verify')->
			setType('password')->
			setLabel('Verify Password')->
			addRule('equalTo', 'password');
								// Set to the actual ID until I figure out a better way to handle that


		$memberGroupRecords = new ObjectRelationshipMapper('memberGroup');
		$memberGroupRecords->is_system = 0;
		$memberGroupRecords->select();
		$membergroups = $memberGroupRecords->resultsToArray();

		$this->changeSection('memberGroups');
		$this->setLegend('Member Groups');
		foreach($membergroups as $memberGroup)
		{
			if($memberGroup['memgroup_name'] == 'Guest')
				continue;

			$this->createInput('memberGroups')->
				setType('checkbox')->
				setLabel($memberGroup['memgroup_name'])->
				property('value', $memberGroup['memgroup_id']);
		}
	}


	protected function populateCustomInputs()
	{
		$membergroupInputs = $this->getInput('memberGroups');

		foreach($membergroupInputs as $memberGroupInput)
		{
			if(isset($memberGroupInput->properties['value']))
			{
				$value = $memberGroupInput->properties['value'];
				if(is_numeric($value))
				{
					$membergroup = ModelRegistry::loadModel('MemberGroup', $value);
					if($membergroup->containsUser($this->model->getId()))
					{
						$memberGroupInput->check(true);
					}
				}
			}
		}
	}

	protected function processCustomInputs($input)
	{
		if(isset($input['model_allowLogin']) && !isset($input['password']))
			return false;

		if(isset($input['password']))
			$this->model['password'] = $input['password'];

		unset($this->model['membergroups']);
		$this->model['membergroups'] = isset($input['memberGroups']) ? $input['memberGroups'] : array();

		return true;
	}
}

?>