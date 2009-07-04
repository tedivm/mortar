<?php

class BentoBaseActionUserEdit extends ModelActionEdit
{

	protected function getForm()
	{
		$parentForm = parent::getForm();
		$membergroupInputs = $parentForm->getInput('memberGroups');

		foreach($membergroupInputs as $memberGroupInput)
		{
			if(isset($memberGroupInput->properties['value']))
			{
				$value = $memberGroupInput->properties['value'];
				if(is_numeric($value))
				{
					$membergroup = new MemberGroup($value);
					if($membergroup->containsUser($this->model->getId()))
					{
						$memberGroupInput->check(true);
					}
				}
			}
		}

		return $parentForm;
	}

	protected function processInput($input)
	{
		if(isset($input['model_allowLogin']) && !isset($input['password']))
			return false;

		if(isset($input['password']) && $input['password'] != '')
			$this->model['password'] = $input['password'];

		unset($this->model['membergroups']);
		$this->model['membergroups'] = isset($input['memberGroups']) ? $input['memberGroups'] : array();

		return parent::processInput($input);
	}
}

?>