<?php 

class ModelActionLocationBasedEditUserPermissions extends ModelActionLocationBasedEditGroupPermissions {

	/**
	 * This defines the permission action that the user needs to run this. Permissions are based off of an action and
	 * a resource type, so this value is used with the model type to generate a permissions object
	 *
	 * @access public
	 * @var string
	 */
	public static $requiredPermission = 'Admin';

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