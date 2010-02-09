<?php

class MortarActionMemberGroupEdit extends ModelActionEdit
{
	protected function getForm()
	{
		$parentForm = parent::getForm();

		$name = $parentForm->getInput('model_name');
		$name->setValue($this->model->name);

		return $parentForm;
	}
}

?>