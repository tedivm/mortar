<?php

class MortarActionUserAdd extends ModelActionAdd
{
	protected function processInput($input)
	{
		if(isset($input['model_allowLogin']) && !isset($input['password']))
			return false;

		if(isset($input['password']))
			$this->model['password'] = $input['password'];

		unset($this->model['membergroups']);
		$this->model['membergroups'] = isset($input['memberGroups']) ? $input['memberGroups'] : array();

		return parent::processInput($input);
	}
}

?>