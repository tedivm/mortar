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
	public $allowed_actions = array();

	public function __construct($location, $user)
	{
		

		if(!is_int($user))
		{
			$user = $user->id;
		}
				
		$this->user = $user;
		
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
				$allowed_actions = $parent_permissions->allowed_actions;
				unset($parent_permissions);
			}
			
			$usergroup_permissions = $this->load_usergroup_permissions($this->location->location_id());
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
			$cache->store_data($allowed_actions);
		}
		$this->allowed_actions = $allowed_actions;
	}
	
	public function is_allowed($action)
	{
		if(IGNOREPERMISSIONS)
			return true;
		
		return in_array($action, $this->allowed_actions);
	}
	
	protected function load_usergroup_permissions($id)
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT actions.action_name, permissionsprofile_has_actions.permission_status
			FROM actions
			LEFT JOIN (permissionsprofile_has_actions, group_permissions, user_in_member_group)
				ON (actions.action_id = permissionsprofile_has_actions.action_id 
				AND permissionsprofile_has_actions.perprofile_id = group_permissions.perprofile_id
				AND group_permissions.memgroup_id = user_in_member_group.memgroup_id)
			WHERE
			group_permissions.location_id = ? AND user_in_member_group.user_id = ?");
		$stmt->bind_param_and_execute('ii', $id, $this->user);
		return $this->adjust_action($stmt);
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
	
	
}




interface intlocation
{
	public function parent_location();
	
	public function location_id();
	
	public function resource_type();
}


class Location implements intlocation 
{
	public $id;
	public $parent;
	
	public $name;
	public $resource;
	
	public $inherits = true;
	
	public $meta = array();
	
	public $siteId;

	protected $directoryTypes = array('directory', 'site');
	
	
	/*
	
	
	*/
	
	
	
	
	
	public function __construct($id = '')
	{
		if($id != '')
		{
			$this->id = $id;
			$cache = new Cache('locations', $this->id, 'info');
			!$locationInfo = $cache->get_data();
			
			if(!$cache->cacheReturned)
			{
				$db_location = new ObjectRelationshipMapper('locations');
				$db_location->location_id = $this->id;
				$db_location->select('1');
				

				$locationInfo['parentId'] = $db_location->location_parent;
				$locationInfo['id'] = $db_location->location_id;
				$locationInfo['resource'] = $db_location->location_resource;
				$locationInfo['name'] = $db_location->location_name;
				$locationInfo['inherits'] = ($db_location->inherit == 1);
				$locationInfo['siteId'] = $this->getSite();
				
				$db_meta = new ObjectRelationshipMapper('location_meta');
				$db_meta->location_id = $this->id;
				if($db_meta->select())
				{
					$results = $db_meta->resultsToArray();
					
					foreach($results as $row)
					{
						$meta[$row['name']] = $row['value'];
					}
					
					$locationInfo['meta'] = $meta;
				}
				
				$cache->store_data($locationInfo);
			}
			
			
			$this->parent = ($locationInfo['parentId'] > 0) ? new Location($locationInfo['parentId']) : false;
			
			$this->siteId = $locationInfo['siteId'];
			$this->resource = $locationInfo['resource'];
			$this->name = $locationInfo['name'];
			$this->inherits = $locationInfo['inherits'];
			$this->meta = $locationInfo['meta'];
		}
		
	}
	
	
	public function parent_location()
	{
		return $this->parent;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	//need to get rid of that
	public function location_id()
	{
		return $this->id;
	}
	
	public function resource_type()
	{
		return $this->resource;
	}
	
	
	public function save()
	{
		$db_location = new ObjectRelationshipMapper('locations');
		
		if($this->id > 0)
		{
			$db_location->location_id = $this->id ;
			$db_location->select('1');
		}
		
		
		if(($this->parent instanceof Location))
		{
			$parentId = $this->parent->location_id();
			
			if(is_numeric($parentId))
			{
				$db_location->location_parent = $parentId;
			}
			
		}elseif(is_numeric($this->parent)){
			$db_location->location_parent = $this->parent;
		}
		
		
		$db_location->location_name = $this->name;
		$db_location->location_resource = $this->resource;
		
		if($parentid)
			$db_location->location_parent = $parentid;

		$db_location->inherit = ($this->inherits) ? 1 : 0;
		
		$result = $db_location->save();

		$this->id = $db_location->location_id;
		
		
		$metaDelete = new ObjectRelationshipMapper('location_meta');
		$metaDelete->location_id = $this->id;
		$metaDelete->delete(0);
		
		if(is_array($this->meta))
		{
			foreach($this->meta as $name => $value)
			{
				$metaAdd = new ObjectRelationshipMapper('location_meta');
				$metaAdd->location_id = $this->id;
				$metaAdd->name = $name;
				$metaAdd->value = $value;
				$metaAdd->save();
			}
		}
	}
	
	public function inheritsPermission()
	{
		return ($this->inherits == 1);
	}
	
	
	public function getChildByName($name)
	{
		$cache = new Cache('locations', $this->id, 'child', $name);
		$childId = $cache->get_data();
		if(!$cache->cacheReturned)
		{
			$db = db_connect('default_read_only');
			
			$stmt = $db->stmt_init();
			

			$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ? AND location_name = ?');
			$stmt->bind_param_and_execute('is', $this->id, $name);
	
			$childLocation = $stmt->fetch_array();
			
			$childId = $childLocation['location_id'];
				
			$cache->store_data($childId);
		}				
		
		if(is_int($childId))
		{
			return new Location($childId);
		}else{
			return false;
		}
		
	}
	
	public function getChildren($type = 'all')
	{
		
		$cache = new Cache('locations', $this->id, 'children', $type);
		
		if(!$childrenIds = $cache->get_data())
		{
			$db = db_connect('default_read_only');
			
			$stmt = $db->stmt_init();
			
			if($type != 'all')
			{
				$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ? AND location_resource = ?');
				$stmt->bind_param_and_execute('is', $this->id, $type);
			}else{
				$stmt->prepare('SELECT location_id FROM locations WHERE location_parent = ?');
				$stmt->bind_param_and_execute('i', $this->id);
			}
			
			
			while($childLocation = $stmt->fetch_array())
			{
				$childrenIds[] = $childLocation['location_id'];
				
			}
			
		
			$cache->store_data($childrenIds);
			
		}		
		
		
		if(!$childrenIds || count($childrenIds) < 1)
		{
			return false;
		}

		foreach($childrenIds as $id)
		{
			$locations[] = new Location($id);
		}
		
		return $locations;
		
	}
	
	public function meta($name)
	{
		return (isset($this->meta[$name])) ? $this->meta[$name] : false;
	}
	
	public function __toString()
	{
		if(isset($this->parent))
		{
			$output = (string) $this->parent;
		}
		
		if($this->name == 'root'){
			return '/';
		}
		
		$output .= $this->name;
		
		if($this->resource == 'directory' || $this->resource == 'site')
			$output .= '/';
			
		return $output;
	}
	
	public function getSite()
	{
		if($this->resource == 'site'){
			$siteObject = new ObjectRelationshipMapper('sites');
			$siteObject->location_id = $this->id;
			$siteObject->select(1);
			
			if($siteObject->total_rows() > 0)
				$site = $siteObject->site_id;
			else
				$site = false;
			
		}elseif($this->parent instanceof Location){
			$site = $this->parent->getSite();
		}
		
		return $site;
	}
	

	public function getTreeArray($types = '', $isFirst = true)
	{
		if(is_string($types) && strlen($types) > 0)
		{
			$types = array($types);
		}elseif(!is_array($types)){
			$types = array();
		}
		
		if(in_array('directory', $types))
			$types = array_merge($types, $this->directoryTypes);
		
		
		$outputArray = array();
		if((count($types) < 1 || in_array($this->resource, $types)) && $this->name != 'root')// && !(in_array($this->resource, $this->directoryTypes) && !$listDirectory))
		{
			$outputArray[$this->id] = (string) $this;
		}

		
		if(count($type) > 0)
		{
			if(!in_array('directory', $types))
				$types = array_merge($types, $this->directoryTypes);
			
			$children = array();
			foreach($types as $type)
			{
				$children = array_merge($children, $this->getChildren($type));
			}
		}else{
			$children = $this->getChildren();
		}
			
		if(is_array($children))
		{
			foreach($children as $child)
			{
				$outputArray = array_merge($outputArray, $child->getTreeArray($types, false));
			}
		}
		
		if($isFirst)
			asort($outputArray);
		
		
		return $outputArray;
			
	}
	
	
}

?>
