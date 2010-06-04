<?php 

class ModelActionLocationBasedEditUserPermissions extends ModelActionLocationBasedEditGroupPermissions
{
        public static $settings = array( 'Base' => array('headerTitle' => 'Edit User Permissions') );

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Admin';

	/**
	 * If this action is loaded without a group property set as a number, it redirects back to the
	 * GroupPermissions action to select a group; then, it calls the logicPass() function of
	 * the parent to access the grandparent's logic() function.
	 *
	 * @access public
	 */
	public function logic()
	{
		$query = Query::getQuery();
		if ( (!isset($query['user'])) || (!is_numeric($query['user'])) ) {
			$url = Query::getUrl();
			$url->action = 'UserPermissions';
			unset($url->user);
			$this->ioHandler->addHeader('Location', (string) $url);
		}
		parent::logicPass();
	}

	/**
	 * This sets the mode ('user') and specific group number for the permission form which
	 * needs to be generated, then passes these off to the parent function which actually
	 * generates the form.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$query = Query::getQuery();
		$mode = 'user';
		$form = new Form('location_user_permissions');
		$user = $query['user'];
		
		if( (!isset($query['user'])) || (!is_numeric($query['user'])) )
			return $form;

		return $this->getPermissionsForm($mode, $form, $user);
	}


}

?>