<?php

class MortarActionMemberGroupAdd extends ModelActionAdd
{

	protected function processInput($input)
	{
		$this->model['is_system'] = 0;

		return parent::processInput($input);
	}
}

?>