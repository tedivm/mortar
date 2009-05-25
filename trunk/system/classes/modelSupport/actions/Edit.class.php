<?php
/**
 * BentoBase
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This class handles editing resources that are already present in the system. It is largely based on the
 * ModelActionEdit class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedEdit extends ModelActionLocationBasedAdd
{
	/**
	 * This function calls the parent::getForm function, but then overwrites the default values with the actual values
	 * the model has set.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$form = parent::getForm();
		$inputGroups = $this->getInputGroups(($form->getInputList()));

		foreach($inputGroups['model'] as $name)
		{
			$input = $form->getInput('model_' . $name);
			$input->setValue($this->model[$name]);
		}

		if(isset($inputGroups['location']))
		{
			if(in_array('name', $inputGroups['location']))
			{
				$input = $form->getInput('location_name');
				$input->setValue($this->model->getLocation()->getName());
			}
		}

		return $form;
	}

	/**
	 * Because we are inheriting from the Add class, which overwrites this function to call from the parent, we need
	 * to place this back in here.
	 *
	 * @access protected
	 */
	protected function setPermissionObject()
	{
		$user = ActiveUser::getInstance();
		$this->permissionObject = new Permissions($this->model->getLocation(), $user);
	}
}

?>