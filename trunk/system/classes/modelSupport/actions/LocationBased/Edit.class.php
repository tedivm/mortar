<?php
/**
 * Mortar
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
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Edit';

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

		if(isset($inputGroups['model']))
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

			if(in_array('owner', $inputGroups['location']))
			{
				$input = $form->getInput('location_owner');
				$input->setValue($this->model->getLocation()->getOwner());
			}

			if(in_array('groupOwner', $inputGroups['location']))
			{
				$input = $form->getInput('location_groupOwner');
				$input->setValue($this->model->getLocation()->getOwnerGroup());
			}
			if(in_array('publishDate', $inputGroups['location']))
			{
				$input = $form->getInput('location_publishDate');
				$pubdate = date( 'm/d/y h:i a' , $this->model->getLocation()->getPublishDate()); 
				$input->setValue($pubdate);
			}
		}

		return $form;
	}

	/**
	 * This class checks to make sure the user has permission to access this action. If passed an argument it will check
	 * for other action types at this location, with this resource (this is useful for checking before redirecting to a
	 * different action on the same location).
	 *
	 * @param string $action
	 * @return bool
	 */
	public function checkAuth($action = NULL)
	{
		$action = isset($action) ? $action : staticHack(get_class($this), 'requiredPermission');
		return $this->model->checkAuth($action);
	}
}

?>