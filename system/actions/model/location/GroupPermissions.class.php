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
 * This action is the portal action for managing Group Permissions. It creates a form which
 * allows the user to select a Group, and then redirects to the EditGroupPermissions action.
 *
 * @package System
 * @subpackage ModelSupport
 */

class ModelActionLocationBasedGroupPermissions extends ModelActionLocationBasedAdd {

        public static $settings = array('Base' => array('headerTitle' => 'Group Permissions', 'useRider' => false) );

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Admin';


	/**
	 * This function produces a simple form for selecting a Group.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$form = new Form('group_permissions');

		$form->changeSection('group')->setLegend('Group');

		$form->createInput('group')->setType('membergroup')->setLabel('Group');

		return $form;
	}

	protected function processInput($input)
	{
                return $this->model->save();
	}


	/**
	 * On submitting the action, this function overrides the standard redirection;
	 * it redirects back to itself if no group is selected, otherwise it contructs
	 * a URL for the EditGroupPermissions action for the selected group.
	 *
	 * @access protected
	 * @return Url
	 */
	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$inputs = $this->form->checkSubmit();
                $group = isset($inputs['group']) ? $inputs['group'] : null;

                $locationId = $this->model->getLocation()->getId();
                $url = new Url();
                $url->locationId = $locationId;
                $url->format = $query['format'];
                $url->action = isset($group) ? 'EditGroupPermissions' : 'GroupPermissions';
                if(isset($group))
                	$url->property('group', $group);
                return $url;
	}

	public function viewAdmin($page)
	{
		$output = parent::viewAdmin($page);
		return $output;
	}

}


?>