<?php


class ModelActionLocationBasedEditGroupPermissions extends ModelActionLocationBasedEdit
{

        public $adminSettings = array( 'headerTitle' => 'Edit Group Permissions' );

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
	 * GroupPermissions action to select a group; then, it redirects to perform the Edit action's
	 * logic function.
	 *
	 * @access public
	 */
	public function logic()
	{
		$query = Query::getQuery();
		if ( (!isset($query['group'])) || (!is_numeric($query['group'])) ) {
			$url = Query::getUrl();
			$url->action = 'GroupPermissions';
			unset($url->group);
			$this->ioHandler->addHeader('Location', (string) $url);
		}
		$this->logicPass();
	}

	/**
	 * This function calls back to the Edit logic function so that UserPermissions can call back
	 * to it without also executing the GroupPermissions redirect.
	 *
	 * @access protected
	 */
	protected function logicPass()
	{
		parent::logic();
	}

	/**
	 * This sets the mode ('group') and specific group number for the permission form which
	 * needs to be generated, then passes these off to a function that actually generates 
	 * the form. This function is overloaded by EditUserPermissions.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function getForm()
	{
		$query = Query::getQuery();
		$mode = 'group';
		$form = new Form('location_group_permissions');
		$user = $query['group'];
		
		if( (!isset($query['group'])) || (!is_numeric($query['group'])) )
			return $form;

		return $this->getPermissionsForm($mode, $form, $user);
	}
	/**
	 * This action loads two sets of permissions: the local permissions from a UserPermission or
	 * GroupPermission action, and the full set of cascading permissions in the form of a
	 * Permission Matrix. It then generates a series of form sections, each containing select boxes
	 * that default to the current local permission, plus text describing all cascading permissions
	 * which apply to the chosen action and model at the current location. The hidden data encodes
	 * the mode and user/group ID to enable saving of Permissions in the correct place.
	 *
	 * @access protected
	 * @var $mode string
	 * @var $form Form
	 * @var $user int
	 * @return Form
	 */
	protected function getPermissionsForm($mode, $form, $user)
	{	
		$location = $this->model->getLocation();

		$permissions = $this->loadPermissionMatrix($mode, $user);
		$localPermission = ($mode === 'user')
			? new UserPermission($location->getId(), $user)
			: new GroupPermission($location->getId(), $user);

		$permissionList = $localPermission->getPermissionsList();

		$modelList = ModelRegistry::getModelList();
		array_unshift($modelList, "Base");
		$actionList = PermissionActionList::getActionList();

		$form->changeSection('header-rows')->setLegend('Header Rows');

		foreach($actionList as $action)
			$form->createInput($action . '_header')->setLabel($action)->setType('hidden');
		
		foreach($modelList as $model) {
			$form->changeSection($model)->setLegend($model);

			$i = 0;
			foreach($actionList as $action) {
				$i = PermissionActionList::getAction($action);
				$selected = array('true' => array(), 'false' => array(), 'unset' => array());

				$desc = (isset($permissions[$model]) && isset($permissions[$model][$i]))
					? $this->distillPermissionsList($permissions[$model][$i])
					: '';
				$form->createInput($model . '_' . $action . '_setting')
					->setType('select')->setPosttext($desc)->setLabel($action);

				if(!(isset($permissionList[$model][$i])))
					$selected['unset'] = array('selected' => 'yes');
				elseif($permissionList[$model][$i])
					$selected['true'] = array('selected' => 'yes');
				else
					$selected['false'] = array('selected' => 'yes');

				$form->getInput($model . '_' . $action . '_setting')
					->setOptions('true', 'Allowed', $selected['true'])
					->setOptions('false', 'Forbidden', $selected['false'])
					->setOptions('unset', 'Unset', $selected['unset']);
			}
		}
		$form->changeSection('hiddenData')->setLegend('Hidden Data');
		$form->createInput('mode')->setType('hidden')->setValue($mode);
		$form->createInput('userId')->setType('hidden')->setValue($user);
		
		return $form;
	}

	/**
	 * This sets the mode ('group') and specific group number for the permission form which
	 * needs to be generated, then passes these off to a function that actually generates 
	 * the form. This function is overloaded by EditUserPermissions.
	 *
	 * @access protected
	 * @return Form
	 */
	protected function processInput($input)
	{
		$location = $this->model->getLocation();

		$modelList = ModelRegistry::getModelList();
		array_unshift($modelList, "Base");
		$actionList = PermissionActionList::getActionList();
		
		$mode = $input['mode'];
		$user = $input['userId'];
		
		$permissions = ($mode === 'user')
			? new UserPermission($location->getId(), $user) 
			: new GroupPermission($location->getId(), $user);

		foreach($modelList as $model) 
			foreach($actionList as $action) 
				if(isset($input[$model.'_'.$action.'_setting'])) {
					if ($input[$model.'_'.$action.'_setting'] === 'true') $setting = true;
					if ($input[$model.'_'.$action.'_setting'] === 'false') $setting = false;
					if ($input[$model.'_'.$action.'_setting'] === 'unset') $setting = 'unset';
					$permissions->setPermission($model, $action, $setting);
				}

		$permissions->save();

		return $location->save();
	}

	/**
	 * This defines a giant horrific array containing relevant Permissions info. The structure is something like
	 * $matrix[Resource][ActionByNumber][LocationById], pointing to a set of arrays that encode the source and value
	 * of individual permission settings that apply to the current location.
	 *
	 * @param string $mode
	 * @param int|Model $user
	 * @return array
	 */	
	protected function loadPermissionMatrix($mode, $user)
	{
		$permissionMatrix = array();

		$location = $this->model->getLocation();
		$rootPath = $location->getPathToRoot();
		
		foreach($rootPath as $locale) {
			$location = new Location($locale);
			
			if ($mode === 'user') {
				$userPermissions = new UserPermission($location->getId(), $user);
				$userPermissionsList = $userPermissions->getPermissionsList();

				foreach ($userPermissionsList as $action => $perms) 
					foreach($perms as $num => $perm) 
						$permissionMatrix[$action][$num][$locale][] = array('source' => 'user', 'value' => $perm);

				$userModel = ModelRegistry::loadModel('User', $user);
	        	        $memberGroups = $userModel['membergroups'];
	        	} else
	        		$memberGroups = array($user);

        	        foreach($memberGroups as $memberGroup) {
        	        	$groupPermissions = new GroupPermission($location->getId(), $memberGroup); 
        	        	$groupPermissionsList = $groupPermissions->getPermissionsList();
      				
				foreach ($groupPermissionsList as $model => $perms) 
					foreach($perms as $action => $perm)
						$permissionMatrix[$model][$action][$locale][] = 
							array('source' => 'group', 'groupId' => $memberGroup, 'value' => $perm);
        	        }
		}
		return $permissionMatrix;

	}


	/**
	 * Using a permission list extracted from the larger PermissionMatrix, this generates a list which describes each
	 * cascading permission set for the resource/action combination in question, which is folded into the permission
	 * form.
	 *
	 * @access protected
	 * @var $permissionList array
	 * @return Form
	 */
	protected function distillPermissionsList($permissionList)
	{
		$list = '<span class="permission_details">';
		foreach($permissionList as $locale => $perms) {
			foreach($perms as $perm) {
				$value = ($perm['value']) ? 'true' : 'false';
				if (isset($perm['groupId'])) {
					$mg = ModelRegistry::loadModel('MemberGroup', $perm['groupId']);
					$group = (isset($mg) && $mg)
						? '<a href="' . $mg->getUrl() . '">' . $mg['name'] . '</a>'
						: $perm['groupId'];
				}
				if ($loc = new Location($locale)) {
					$locName = $loc->getName();
					if ($locName === 'root')
						$locat = $locName;
					else
						$locat = '<a href="' . $loc->getResource()->getUrl() . '">' . $locName . '</a>';
				}
				$list .= ($perm['source'] === 'user')
					? "Set <span class='permission_$value'>$value</span> for user at "
					. "location <span class='permissions_location'>$locat</span>. "
					: "Set <span class='permission_$value'>$value</span> for group " 
					. "<span class='permissions_group'>$group</span> at location " 
					. "<span class='permissions_location'>$locat</span>. ";
			}
		}

		return $list . '</span>';
	}
	
}

?>