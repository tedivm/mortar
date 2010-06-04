<?php

class MortarMemberGroupForm extends Form
{
	protected function define()
	{
		$this->changeSection('Info');

		$this->createInput('model_name')->
			setLabel('Member Group Name')->
			addRule('required');
	}

}

?>