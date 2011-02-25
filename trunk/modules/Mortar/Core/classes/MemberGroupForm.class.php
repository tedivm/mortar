<?php

class MortarCoreMemberGroupForm extends ModelForm
{
	protected function createCustomInputs()
	{
		$this->changeSection('Info');

		$this->createInput('model_name')->
			setLabel('Member Group Name')->
			addRule('required');
	}

	protected function processCustomInputs($input)
	{
		$this->model['is_system'] = 0;
		return true;
	}

	protected function populateCustomInputs()
	{
		$name = $this->getInput('model_name');
		$name->setValue($this->model->name);
	}
}

?>