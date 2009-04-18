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
	protected $user;
	protected $location;

	// $permission[type][action] = permissions
	protected $permissions = array();


	public function __construct($location, $userId)
	{

		if(IGNOREPERMISSIONS)
			return;

		if($userId instanceof User){
			$this->user = $userId;
			$userId = $this->user->getId();
		}elseif(is_numeric($userId)){
			$this->user = new User();
			$this->user->load_user($userId);
		}elseif($userId instanceof ActiveUser){
			$this->user = new User();
			$this->user->load_user($userId->getId());
			$userId = $this->user->getId();
		}else{
			throw new TypeMismatch(array('User', $userId));
		}

		if(is_int($location))
			$location = new Location($location);

		if(!($location instanceof Location))
			throw new TypeMismatch(array('Location', $location));


		$this->location = $location;

		$this->permissions = $this->loadPermissions();

	}

	protected function loadPermissions()
	{
		$memberGroupPermissionsArray = array();
		$memberGroups = $this->user->getMemberGroups();

		if($resourceOwner = $this->location->getOwner() && $resourceOwner->getId() == $this->user->getId())
			$memberGroups[] = MemberGroup::lookupIdbyName('ResourceOwner');

		if($memberGroup = $this->location->getOwnerGroup() && in_array($memberGroup->getId(), $memberGroups))
			$memberGroups[] = MemberGroup::lookupIdbyName('ResourceGroupOwner');



		foreach($memberGroups as $memberGroup)
		{
			$memgroupPermissions = new GroupPermission($memberGroup, $this->location->getId());
			$memberGroupPermissionsArray = $this->mergePermissions($memberGroupPermissionsArray,
															$memgroupPermissions->getPermissions());
		}

		$userPermissions = new UserPermission($this->user->getId(), $this->location->getId());
		$userPermissionsArray = $userPermissions->getPermissions();
		return $this->mergePermissions($memberGroupPermissionsArray, $userPermissionsArray);
	}

	protected function mergePermissions($current, $new)
	{
		if(!is_array($current))
			throw new TypeMismatch(array('Array', $current));

		if(!is_array($new))
			throw new TypeMismatch(array('Array', $new));

		// Run check to make sure we only iterate through the smallest array
		if(count($current, 1) < count($new, 1))
		{
			$tmp = $current;
			$current = $new;
			$new = $tmp;
			unset($tmp);
		}

		foreach($new as $typeIndex => $typeActions)
		{
			foreach($typeActions as $actionIndex => $actionValue)
			{
				switch (true)
				{
					case ($current[$typeIndex][$actionIndex] === true):
					case ($actionValue === true):
						$current[$typeIndex][$actionIndex] = true;
						break;

					case ($current[$typeIndex][$actionIndex] == false):
					case($actionValue == false):
						$current[$typeIndex][$actionIndex] = false;
						break;

					case ($current[$typeIndex][$actionIndex] == 'inherit'):
					case ($actionValue == 'inherit'):
					default:
						unset($current[$typeIndex][$actionIndex]);
				}
			}
		}

		return $current;
	}

	public function isAllowed($action, $type = null)
	{
		// This should only be used when testing the permissions system, particularly when breaking it but needing
		// to perform some action, like resetting the cache
		if(IGNOREPERMISSIONS)
			return true;

		if(!is_numeric($action))
			$action = PermissionActionList::getAction($action);

		// Check to see if user is in the superuser group
		$adminMemberGroup = new MemberGroup(MemberGroup::lookupIdbyName('SuperUser'));
		if($adminMemberGroup->containsUser($this->user->getId()))
			return true;

		// Check to see if the universal action is set at this location
		if(isset($this->permissions['universal'][$action]) && $this->permissions['universal'][$action] === true)
			return true;

		if(is_null($type))
			$type = $this->location->getType();

		// If the permission isn't set check the parent
		if(!isset($this->permissions[$type][$action]))
		{
			$parentLocation = $this->location->getParent();

			// If you've inherited back to the top of the tree and didn't find anything, give it a negative
			if($parentLocation === false || !$this->location->inheritsPermission())
				return false;

			//Check the parent location
			$parentPermission = new Permissions($parentLocation->getId(), $this->user);

			return $parentPermission->isAllowed($action, $type);

		}else{
			return ($this->permissions[$type][$action] === true);
		}
	}

	public function checkAuth($action)
	{
		depreciationError();
		return $this->isAllowed($action);
	}

}

class UserPermission
{
	protected $type = 'user';
	protected $typeId = 'user_id';
	protected $location;
	protected $id;

	// $permission[type][action] = permissions
	protected $permissions = array();

	public function __construct($id, $location)
	{
		if(!is_numeric($id))
			throw new TypeMismatch(array('Integer', $id));

		$this->id = $id;

		if($location instanceof Location)
			$location = $location->getId();

		if(!is_int($location))
			throw new TypeMismatch(array('Integer', $location));

		$this->location = $location;

		$this->permissions = $this->loadPermissionsFromDatabase();
	}

	public function setPermission($resource, $action, $permission)
	{
		if(!is_numeric($action))
			$action = PermissionActionList::getAction($action);

		if(!is_numeric($action))
			throw new TypeMismatch(array('Integer', $action));

		if($permission == 1 || $permission == true)
		{
			$this->permissions[$resource][$action] = true;

		}elseif($permission == 0 || $permission === false){

			$this->permissions[$resource][$action] = false;

		}else{

			unset($this->permissions[$resource][$action]);
		}
	}

	public function getPermissions()
	{
		return $this->permissions;
	}

	protected function loadPermissionsFromDatabase()
	{
		$type = $this->type;
		$typeId = $this->typeId;
		$id = $this->id;

		$cache = new Cache('permissions', $type, $id, $this->location);
		$permissions = $cache->getData();
		if(!$cache->cacheReturned)
		{
			$permissions = array();
			$db = dbConnect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT ' . $type . 'Permissions.permission, '. $type .'Permissions.resource,
								actions.action_id
							FROM actions, ' . $type . 'Permissions
							WHERE (actions.action_id = ' . $type . 'Permissions.action_id)
								AND ' . $type . 'Permissions.location_id = ?
								AND ' . $type . 'Permissions.' . $typeId . ' = ?');
			$stmt->bind_param_and_execute('ii', $this->location, $id);
			if($stmt->num_rows > 0)
			{
				$tmp_array = array();
				while($permissionRow = $stmt->fetch_array())
				{
					$resource = ($permissionRow['resource']) ? $permissionRow['resource'] : 'base';

					if($permissionRow['permission'] == 1)
					{
						$permissions[$resource][$permissionRow['action_id']] = true;
					}elseif($permissionRow['permission'] == 0){

						$permissions[$resource][$permissionRow['action_id']] = false;
					}
					// if its not true, and not false, the only option is to inherit which we are treating as unset
				}
			}
			$cache->storeData($permissions);
		}
		return $permissions;
	}

	public function save()
	{
		$tableName = $this->type . 'Permissions';
		$db = DatabaseConnection::getConnection('default');

		$db->autocommit(false);

		try
		{
			$clearStmt = $db->stmt_init();
			$clearStmt->prepare('DELETE FROM ' . $tableName . '
									WHERE ' . $tableName . '.' . $this->typeId . ' = ? AND location_id = ?' );


			$clearStmt->bindAndExecute('ii', $this->id, $this->location);

			foreach($this->permissions as $typeIndex => $typeActions)
			{
				foreach($typeActions as $actionIndex => $actionValue)
				{
					if($actionValue === true)
					{
						$saveValue = 1;
					}elseif($actionValue === false){
						$saveValue = 0;
					}else{
						// No need to save inherit, since its the default
						continue;
					}

					$saveStmt = $db->stmt_init();
					$saveStmt->prepare('INSERT INTO ' . $tableName .
										'(location_id, action_id, resource, permission, ' . $this->typeId . ')
										Values (?, ?, ?, ?, ?)');

					$saveStmt->bindAndExecute('iisii', $this->location,
															$actionIndex,
															$typeIndex, $saveValue, $this->id);
				}
			}

			Cache::clear('permissions', $typeIndex, $this->id, $this->location);

		}catch(Exception $e){
			$db->rollback();
			$db->autocommit(true);
			throw new BentoError('Error while inserting ' . $type . 'Permissions');
		}


		// We place the commit in here so as not to interfere with any changed to autocommit outside this class
		$db->commit();
		$db->autocommit(true);

		return true;
	}

}

class GroupPermission extends UserPermission
{
	protected $type = 'group';
	protected $typeId = 'memgroup_id';
}

class PermissionLists
{
	protected $userId;
	protected $permissions;

	public function __construct($userId)
	{
		$this->userId = $userId;

		if(IGNOREPERMISSIONS !== true)
			$this->load();
	}

	public function checkAction($type, $action)
	{
		$type = strtolower($type);
		$action = strtolower($action);
		if(IGNOREPERMISSIONS === true)
			return true;

		return (isset($this->permissions[$type]) && in_array($action, $this->permissions[$type]));
	}

	protected function load()
	{
		$user = new User($this->userId);
		$memberGroups = $user->getMemberGroups();

		$permissions = array();
		$permissions[] = $this->loadUserPermissions($user->getId());

		foreach($memberGroups as $memberGroup)
		{
			$permissions[] = $this->loadGroupPermissions($memberGroup);
			//var_dump($this->loadGroupPermissions($memberGroup));
		}
		$this->permissions = call_user_func_array('array_merge_recursive', $permissions);
	}

	protected function loadUserPermissions($userId)
	{
		$cache = new Cache('permissions', 'user', $userId, 'allowedActions');
		$allowedPermissions = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$allowedPermissions = array();

			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();

			$stmt->prepare('SELECT DISTINCT userPermissions.resource as resource, actions.action_name as action
								FROM userPermissions, actions
								WHERE userPermissions.user_id = ?
									AND userPermissions.action_id = actions.action_id
									AND userPermissions.permission = 1');

			$stmt->bindAndExecute('i', $userId);

			while($row = $stmt->fetch_array())
			{
				$allowedPermissions[strtolower($row['resource'])][] = strtolower($row['action']);
			}
			$cache->storeData($allowedPermissions);
		}
		return $allowedPermissions;
	}

	protected function loadGroupPermissions($groupId)
	{
		$cache = new Cache('permissions', 'group', $groupId, 'allowedActions');
		$allowedPermissions = $cache->getData();

		if(!$cache->cacheReturned)
		{
			$allowedPermissions = array();

			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();

			$stmt->prepare('SELECT DISTINCT groupPermissions.resource as resource, actions.action_name as action
								FROM groupPermissions, actions
								WHERE groupPermissions.memgroup_id = ?
									AND groupPermissions.action_id = actions.action_id
									AND groupPermissions.permission = 1');

			$stmt->bindAndExecute('i', $groupId);

			while($row = $stmt->fetch_array())
			{
				$allowedPermissions[strtolower($row['resource'])][] = strtolower($row['action']);
			}
			$cache->storeData($allowedPermissions);
		}
		return $allowedPermissions;
	}
}

class PermissionActionList
{
	static protected $actionList = false;

	static public function clear()
	{
		self::$actionList = false;
	}

	static public function getAction($action)
	{
		if(self::$actionList === false)
			self::loadActionList();

		$output = (isset(self::$actionList[$action])) ? self::$actionList[$action] : false;

		return $output;
	}

	static public function addAction($action)
	{
		if(self::$actionList === false)
					self::loadActionList();

		if(self::getAction($action) !== false)
			return true;

		$db = dbConnect('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO actions (action_name) VALUES (?)');

		if($stmt->bind_param_and_execute('s', $action))
		{

			$id = $stmt->insert_id;

			// All new permissions should be granted to the administrator membergroup.
			$adminPermissions = new GroupPermission(MemberGroup::lookupIdbyName('Administrator'), 1);
			$adminPermissions->setPermission('Universal', $id, true);
			$adminPermissions->save();

			self::clear();
			return true;
		}else{
			return false;
		}
	}

	static protected function loadActionList()
	{
		$db = db_connect('default_read_only');
		$result = $db->query('SELECT action_id, action_name FROM actions');
		$actions = array();
		while($row = $result->fetch_array())
		{
			$actions[$row['action_name']] = $row['action_id'];
		}
		self::$actionList = $actions;
	}

}

?>