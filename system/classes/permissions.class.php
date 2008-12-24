<?php
/**
 * BentoBase
 *
 * A framework for developing modular applications.
 *
 * @package		BentoBase
 * @author		Robert Hafner
 * @copyright		Copyright (c) 2007, Robert Hafner
 * @license		http://www.mozilla.org/MPL/
 * @link		http://www.bentobase.org
 */


class Permissions
{
	public $user;
	public $location;
	public $allowedActions = array();
	public $inheritedActions = array();

	public function __construct($location, $userId)
	{

		if(!($userId instanceof User) && is_numeric($userId))
		{
			$user = new User();
			$user->load_user($userId);
		}elseif($userId instanceof User){
			$user = $userId;
			$userId = $user->getId();
		}elseif($userId instanceof ActiveUser){
			$user = new User();
			$user->load_user($userId->getId());
			$userId = $userId->getId();
		}

		$this->user = $user->getId();

		if(is_int($location))
			$location = new Location($location);

		if(!($location instanceof Location))
			throw new BentoError('Expecting location object.');

		$this->location = $location;


		$cache = new Cache('permissions', $this->location->location_id(), $this->user);
		$allowed_actions = $cache->get_data();
		if(!$cache->cacheReturned)
		{
			$allowed_actions = array();

			if($this->location->parent_location() && $this->location->inheritsPermission())
			{
				$parent_permissions = new Permissions($this->location->parent_location(), $user);
				$inheritedActions = $parent_permissions->getActions();
				unset($parent_permissions);
			}

			$memberGroups = $user->getMemberGroups();
			$usergroup_permissions = array();
			foreach($memberGroups as $groupId)
			{
				if($usergroupActions = $this->load_usergroup_permissions($this->location->location_id(), $groupId))
					$usergroup_permissions = array_merge($usergroup_permissions, $usergroupActions);
			}

			$user_permissions = $this->load_user_permissions($this->location->location_id());

			$tmp = array_merge($usergroup_permissions, $user_permissions);
			foreach($tmp as $name => $value)
			{
				if($value)
				{
					if(!in_array($name, $allowed_actions))
						$allowed_actions[] = $name;
				}else{
					if($key = array_search($name, $allowed_actions))
						unset($allowed_actions[$key]);
				}
			}


			$actions['inherited'] = $inheritedActions;
			$actions['allowed'] = $allowed_actions;

			$cache->store_data($actions);
		}

		if(is_array($actions['inherited']))
			$this->inheritedActions = $actions['inherited'];
		$this->allowedActions = $actions['allowed'];
	}

	public function isAllowed($action)
	{
		if(IGNOREPERMISSIONS)
			return true;

		return in_array($action, $this->getActions());
	}

	public function checkAuth($action)
	{
		return $this->isAllowed($action);
	}

	protected function load_usergroup_permissions($locationId, $memgroupId)
	{
		$cache = new Cache('permissions', 'membergroups', $memgroupId, $locationId);
		$actions = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT actions.action_name, permissionsprofile_has_actions.permission_status FROM actions
				LEFT JOIN (permissionsprofile_has_actions, group_permissions)
					ON (permissionsprofile_has_actions.perprofile_id = group_permissions.perprofile_id
					AND actions.action_id = permissionsprofile_has_actions.action_id)
				WHERE location_id= ? AND memgroup_id = ?");
			$stmt->bind_param_and_execute('ii', $locationId, $memgroupId);

			$actions = $this->adjust_action($stmt);
			$cache->storeData($actions);
		}
		return $actions;
	}

	protected function load_user_permissions($id)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT actions.action_name, permissionsprofile_has_actions.permission_status
			FROM actions
			LEFT JOIN (permissionsprofile_has_actions, user_permissions)
				ON (actions.action_id = permissionsprofile_has_actions.action_id
				AND permissionsprofile_has_actions.perprofile_id = user_permissions.perprofile_id)
			WHERE
			user_permissions.location_id = ? AND user_permissions.user_id = ?");
		$stmt->bind_param_and_execute('ii', $id, $this->user);
		return $this->adjust_action($stmt);
	}

	protected function adjust_action($stmt)
	{
		$tmp_array = array();
		if($stmt->num_rows > 0)
		{
			$tmp_array = array();
			while($permission = $stmt->fetch_array())
			{
				$tmp_array[$permission['action_name']] = ($permission['permission_status'] == 1);
			}
		}
		return $tmp_array;
	}

	public function getActions()
	{
		if(!is_array($this->inheritedActions))
			$this->inheritedActions = array();

		if(!is_array($this->allowedActions))
			$this->allowedActions = array();

		return array_merge($this->inheritedActions, $this->allowedActions);
	}

}



?>