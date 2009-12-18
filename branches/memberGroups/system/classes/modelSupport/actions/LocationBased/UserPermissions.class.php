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
 * This action is the portal action for managing User Permissions. It creates a form which
 * allows the user to select a User, and then redirects to the EditUserPermissions action.
 * It extends from the GroupPermissions action.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedUserPermissions extends ModelActionLocationBasedGroupPermissions
{

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Admin';

	/**
	 * This function produces a simple form for selecting a User.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$form = new Form('user_permissions');

		$form->changeSection('user')->setLegend('User');

		$form->createInput('user')->setType('user')->setLabel('User');

		return $form;
	}

	/**
	 * On submitting the action, this function overrides the standard redirection;
	 * it redirects back to itself if no group is selected, otherwise it contructs
	 * a URL for the EditUserPermissions action for the selected group.
	 *
	 * @access protected
	 * @return Url
	 */
	protected function getRedirectUrl()
	{
		$query = Query::getQuery();
		$inputs = $this->form->checkSubmit();
                $user = isset($inputs['user']) ? $inputs['user'] : null;

                $locationId = $this->model->getLocation()->getId();
                $url = new Url();
                $url->locationId = $locationId;
                $url->format = $query['format'];
                $url->action = isset($user) ? 'EditUserPermissions' : 'UserPermissions';
                if(isset($user))
                	$url->property('user', $user);
                return $url;
	}

}

?>