<?php

class MortarFormPluginFormInputMembergroupCheckSubmit extends MortarFormPluginFormInputUserCheckSubmit
{
	protected $inputName = 'membergroup';

	protected function inputToValue($input)
	{
		$model = ModelRegistry::loadModel('MemberGroup');
		$model->loadByName($input);
		if($id = $model->getId())
			return $id;
		return false;
	}
}

?>