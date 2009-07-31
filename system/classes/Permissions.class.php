<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage User
 */

/**
 * This class handles user permissions
 *
 * @package System
 * @subpackage User
 */
class Permissions
{
	/**
	 * This is the user the object is checking against
	 *
	 * @access protected
	 * @var User
	 */
	protected $user;

	/**
	 * Location that is being checked
	 *
	 * @access protected
	 * @var Location
	 */
	protected $location;

	/**
	 * An array of allowed permissions and actions
	 *
	 * @access protected
	 * @var array
	 */
	protected $permissions = array();


	/**
	 * Constructor takes a location and user
	 *
	 * @param Location|int $location
	 * @param User|int $userId
	 */
	public function __construct($location, $userId)
	{

		if(IGNOREPERMISSIONS)
			return;

		if($userId instanceof Model && $userId->getType() == 'User'){
			$this->user = $userId;
			$userId = $this->user->getId();
		}elseif(is_numeric($userId)){

			if(!($user = ModelRegistry::loadModel('User', $userId)))
				throw new PermissionsError('Invalid user id passed to Permissions class.');

			$this->user = $user;
		}else{
			throw new TypeMismatch(array('User', $userId));
		}

		if(is_numeric($location))
			$location = new Location($location);

		if(!($location instanceof Location))
			throw new TypeMismatch(array('Location', $location));


		$this->location = $location;

		$this->permissions = $this->loadPermissions();

	}

	/**
	 * This loads the permmissions fromt he database and, if allowed, the parent class
	 *
	 * @access protected
	 * @return array
	 */
	protected function loadPermissions()
	{
		$memberGroupPermissionsArray = array();
		$memberGroups = $this->user['membergroups'];

		$resourceOwner = $this->location->getOwner();
		if($resourceOwner && $resourceOwner->getId() == $this->user->getId())
			$memberGroups[] = MemberGroup::lookupIdbyName('ResourceOwner');

		$memberGroup = $this->location->getOwnerGroup();
		if($memberGroup && in_array($memberGroup->getId(), $memberGroups))
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

	/**
	 * Merges two permission arrays
	 *
	 * @param array $current
	 * @param array $new
	 * @return array
	 */
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

	/**
	 * Checks to see if an action is allowed to be performed by the user at this location
	 *
	 * @param string $action
	 * @param string|null $type this represents a resource type. If null, it uses the current location's type.
	 * @return bool
	 */
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

	/**
	 * This is depreciated. Use isAllowed.
	 *
	 * @deprecated
	 * @param string $action
	 * @return bool
	 */
	public function checkAuth($action)
	{
		depreciationWarning();
		return $this->isAllowed($action);
	}

}

/**
 * This class helps the Permissions class by loading the user specific permissions from the database
 *
 * @package System
 * @subpackage User
 */
class UserPermission
{
	/**
	 * This is the permission type, used for some sql
	 *
	 * @access protected
	 * @var string
	 */
	protected $type = 'user';

	/**
	 * This is the column that contains the id needed for the sql
	 *
	 * @access protected
	 * @var string
	 */
	protected $typeId = 'user_id';

	/**
	 * This is the location being checked
	 *
	 * @var Location
	 */
	protected $location;

	/**
	 * This is the id of the user being checked
	 *
	 * @access protected
	 * @var int
	 */
	protected $id;

	/**
	 * An array of allowed permissions and actions
	 *
	 * @access protected
	 * @var array
	 */
	protected $permissions = array();

	/**
	 * Constructor takes the ID and location
	 *
	 * @param int $id
	 * @param Location|int $location
	 */
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

	/**
	 * This method is used to change the permission of an object
	 *
	 * @param string $resource
	 * @param string $action
	 * @param bool $permission
	 */
	public function setPermission($resource, $action, $permission = true)
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

	/**
	 * Returns the permission array
	 *
	 * @return array
	 */
	public function getPermissions()
	{
		return $this->permissions;
	}

	/**
	 * This loads the permissions from the database and returns them as an array
	 *
	 * @access protected
	 * @cache permissions *type *id *location
	 * @return array
	 */
	protected function loadPermissionsFromDatabase()
	{
		$type = $this->type;
		$typeId = $this->typeId;
		$id = $this->id;

		$cache = new Cache('permissions', $type, $id, $this->location);
		$permissions = $cache->getData();
		if($cache->isStale())
		{
			$permissions = array();
			$db = DatabaseConnection::getConnection('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare('SELECT ' . $type . 'Permissions.permission, '. $type .'Permissions.resource,
								actions.action_id
							FROM actions, ' . $type . 'Permissions
							WHERE (actions.action_id = ' . $type . 'Permissions.action_id)
								AND ' . $type . 'Permissions.location_id = ?
								AND ' . $type . 'Permissions.' . $typeId . ' = ?');
			$stmt->bindAndExecute('ii', $this->location, $id);
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

	/**
	 * This function saves the current permission to the database
	 *
	 * @return success status
	 */
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
			throw new PermissionsError('Error while inserting ' . $type . 'Permissions');
		}


		// We place the commit in here so as not to interfere with any changed to autocommit outside this class
		$db->commit();
		$db->autocommit(true);

		return true;
	}

}

/**
 * This class helps the Permissions class by loading the MemberGroup specific permissions from the database
 *
 * @package System
 * @subpackage User
 */
class GroupPermission extends UserPermission
{
	/**
	 * This is the permission type, used for some sql
	 *
	 * @access protected
	 * @var string
	 */
	protected $type = 'group';

	/**
	 * This is the column that contains the id needed for the sql
	 *
	 * @access protected
	 * @var string
	 */
	protected $typeId = 'memgroup_id';
}

/**
 * This class creates a general list (not attached to locations) of actions the user is allowed to perform
 *
 * @package System
 * @subpackage User
 */
class PermissionLists
{
	/**
	 * This is the user id that is being checked
	 *
	 * @access protected
	 * @var int
	 */
	protected $userId;

	/**
	 * This is the array of permission actions
	 *
	 * @access protected
	 * @var array
	 */
	protected $permissions;

	/**
	 * This constructor takes a user id as its argument and loads the permissions
	 *
	 * @param int $userId
	 */
	public function __construct($userId)
	{
		$this->userId = $userId;

		if(IGNOREPERMISSIONS !== true)
			$this->load();
	}

	/**
	 * Checks to see if the permission can be run by this user
	 *
	 * @param string $type
	 * @param string $action
	 * @return bool
	 */
	public function checkAction($type, $action)
	{
		$type = strtolower($type);
		$action = strtolower($action);
		if(IGNOREPERMISSIONS === true)
			return true;

		return (isset($this->permissions[$type]) && in_array($action, $this->permissions[$type]));
	}

	/**
	 * Loads the permissions
	 *
	 * @access protected
	 */
	protected function load()
	{
		$user = ModelRegistry::loadModel('User', $this->userId);
		$memberGroups = $user['membergroups'];

		$permissions = array();
		$permissions[] = $this->loadUserPermissions($user->getId());

		foreach($memberGroups as $memberGroup)
		{
			$permissions[] = $this->loadGroupPermissions($memberGroup);
		}
		$this->permissions = call_user_func_array('array_merge_recursive', $permissions);
	}

	/**
	 * Loads user table permissions and returns them as an array
	 *
	 * @cache permissions user *userId allowedActions
	 * @param int $userId
	 * @return array
	 */
	protected function loadUserPermissions($userId)
	{
		$cache = new Cache('permissions', 'user', $userId, 'allowedActions');
		$allowedPermissions = $cache->getData();

		if($cache->isStale())
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

	/**
	 * Loads group permissions and returns them as an array
	 *
	 * @access protected
	 * @cache permissions group *groupId allowedActions
	 * @param id $groupId
	 * @return array
	 */
	protected function loadGroupPermissions($groupId)
	{
		$cache = new Cache('permissions', 'group', $groupId, 'allowedActions');
		$allowedPermissions = $cache->getData();

		if($cache->isStale())
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

/**
 * This class is used to access the list of actions
 *
 * @package System
 * @subpackage User
 */
class PermissionActionList
{
	/**
	 * An array of actions in the system
	 *
	 * @access protected
	 * @static
	 * @var array
	 */
	static protected $actionList = false;

	/**
	 * This function clears the action list
	 *
	 * @static
	 */
	static public function clear()
	{
		self::$actionList = false;
	}

	/**
	 * Returns the id of the action
	 *
	 * @static
	 * @param string $action
	 * @return int
	 */
	static public function getAction($action)
	{
		if(self::$actionList === false)
			self::loadActionList();

		return (isset(self::$actionList[$action])) ? self::$actionList[$action] : false;
	}

	/**
	 * Adds an action to the system
	 *
	 * @static
	 * @param string $action
	 * @return bool
	 */
	static public function addAction($action)
	{
		if(self::$actionList === false)
					self::loadActionList();

		if(self::getAction($action) !== false)
			return true;

		$db = dbConnect('default');
		$stmt = $db->stmt_init();
		$stmt->prepare('INSERT INTO actions (action_name) VALUES (?)');

		if($stmt->bindAndExecute('s', $action))
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

	/**
	 * Loads the list of actions from the database
	 *
	 * @access protected
	 * @cache permissions actionLookup
	 * @static
	 */
	static protected function loadActionList()
	{

		$cache = new Cache('permissions', 'actionLookup');
		$actions = $cache->getData();

		if($cache->isStale())
		{
			$db = db_connect('default_read_only');
			$result = $db->query('SELECT action_id, action_name FROM actions');
			$actions = array();
			while($row = $result->fetch_array())
			{
				$actions[$row['action_name']] = $row['action_id'];
			}
			$cache->storeData($actions);
		}
		self::$actionList = $actions;
	}

}

class PermissionsError extends CoreError {}
?>