<?php

class BentoBaseUserForm extends Form
{

	protected function define()
	{
		$this->changeSection('Info');
		$this->setLegend('Member Information');

		$this->createInput('name')->
			setLabel('Display/Login Name')->
			addRule('required');

		$this->createInput('email')->
			setLabel('Email')->
			addRule('email');


		$this->createInput('login')->
			setType('checkbox')->
			setLabel('Allow Login');

		$this->createInput('password')->
			setType('password')->
			setLabel('Password');

		$this->createInput('password_verify')->
			setType('password')->
			setLabel('Verify Password')->
			addRule('equalTo', '#' . $this->name . '_password');
								// Set to the actual ID until I figure out a better way to handle that


		$memberGroupRecords = new ObjectRelationshipMapper('member_group');
		$memberGroupRecords->is_system = 0;
		$memberGroupRecords->select();
		$membergroups = $memberGroupRecords->resultsToArray();

		$this->changeSection('memberGroups');
		$this->setLegend('Member Groups');
		foreach($membergroups as $memberGroup)
		{
			if($memberGroup['memgroup_name'] == 'Guest')
				continue;

			$this->createInput('memberGroup')->
				setType('checkbox')->
				setLabel($memberGroup['memgroup_name'])->
				property('value', $memberGroup['memgroup_id']);
		}

	}
}

?>