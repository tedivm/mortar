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
        public static $settings = array( 'Base' => array('headerTitle' => 'Edit', 'useRider' => false) );

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
		$form->populateInputs();
		return $form;
	}

	protected function log()
	{
		$user = ActiveUser::getUser();
		ChangeLog::logChange($this->model, 'edited', $user, 'Read');
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
		$action = isset($action) ? $action : static::$requiredPermission;
		return $this->model->checkAuth($action);
	}
}

?>