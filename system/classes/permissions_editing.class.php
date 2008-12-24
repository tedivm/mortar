<?php

class PermissionProfile
{
	public $allowed_actions = array();
	public $denied_actions = array();

	public $id = 0;
	public $name = '';
	public $description;

	public function add_action($name)
	{
		$this->allowed_actions[] = array('name' => $name);
	}


	public function load($id = '')
	{
		if($id != '')
			$this->id = $id;

		if($this->id > 0)
		{
			$this->load_by_id();
		}elseif($this->name != ''){
			$this->load_by_name();
		}
	}

	public function load_by_name($name = '')
	{
		$db = db_connect('default_read_only');
		if($name != '')
			$this->name = $name;

		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT perprofile_id, perprofile_name, perprofile_description FROM permission_profiles WHERE perprofile_name=? LIMIT 1");
		$stmt->bind_param_and_execute('s', $this->name);

		if($stmt->num_rows > 0)
		{
			$profile = $stmt->fetch_array();
			$this->finish_loading($profile);
		}

	}

	public function load_by_id($id = 0)
	{
		$db = db_connect('default_read_only');



		if($id != '')
			$this->id = $id;

		$stmt = $db->stmt_init();
		$stmt->prepare("SELECT perprofile_name, perprofile_description, perprofile_description FROM permission_profiles WHERE perprofile_id=? LIMIT 1");
		$stmt->bind_param_and_execute('i', $sql_id);


		if($stmt->num_rows > 0)
		{
			$profile = $stmt->fetch_array();
			$this->finish_loading($profile);
		}
	}


	protected function finish_loading($profile)
	{


		$this->id = $profile['perprofile_id'];
		$this->name = $profile['perprofile_name'];
		$this->description = $profile['perprofile_description'];

		$db = db_connect('default_read_only');
		if($this->id > 0){
			$stmt_action = $db->stmt_init();
			$stmt_action->prepare("SELECT actions.action_id, actions.action_name, permissionsprofile_has_actions.permission_status FROM actions, permissionsprofile_has_actions
													WHERE actions.action_id = permissionsprofile_has_actions.action_id AND perprofile_id=?");

			$stmt_action->bind_param_and_execute('i', $this->id);

			while($action_result = $stmt_action->fetch_array())
			{
				if($action_result['permission_status'] == 1)
				{
					$this->allowed_actions[] = array('id' => $action_result['action_id'], 'name' => $action_result['action_name']);
				}else{
					$this->denied_actions[] = array('id' => $action_result['action_id'], 'name' => $action_result['action_name']);
				}
			}
		}
	}

	public function save()
	{
		$db_write = db_connect('default');
		$db_read = db_connect('default_read_only');




		if($this->id == 0)
		{
			$insert_stmt = $db_write->stmt_init();
			$insert_stmt->prepare("INSERT INTO permission_profiles (perprofile_id, perprofile_name, perprofile_description) VALUES (NULL, ?, ?)");
			$insert_stmt->bind_param_and_execute('ss', $this->name, $this->description);
			$this->id = $insert_stmt->insert_id;
		}else{

		}

		foreach($this->allowed_actions as $action)
		{
			$check_action_stmt = $db_read->stmt_init();
			$check_action_stmt->prepare("SELECT action_id FROM actions WHERE action_name = ?");
			$check_action_stmt->bind_param_and_execute('s', $action['name']);

			if($check_action_stmt->num_rows >0)
			{
				$result = $check_action_stmt->fetch_array();
				$action['id'] = $result['action_id'];
			}else{
				$action_insert_stmt = $db_write->stmt_init();
				$action_insert_stmt->prepare("INSERT INTO actions (action_id, action_name) VALUES (NULL, ?)");
				$action_insert_stmt->bind_param_and_execute('s', $action['name']);
				$action['id'] = $action_insert_stmt->insert_id;
			}

			$insert_stmt = $db_write->stmt_init();
			$insert_stmt->prepare("INSERT INTO permissionsprofile_has_actions (perprofile_id, action_id, permission_status) VALUES (?, ?, '1')");
			$insert_stmt->bind_param_and_execute('ii', $this->id, $action['id']);
		}

		foreach($this->denied_actions as $action)
		{
			$check_action_stmt = $db_read->stmt_init();
			$check_action_stmt->prepare("SELECT action_id FROM actions WHERE action_name = ?");
			$check_action_stmt->bind_param_and_execute('s', $action['name']);

			if($check_action_stmt->num_rows >0)
			{
				$result = $check_action_stmt->fetch_array();
				$action['id'] = $result['action_id'];
			}else{
				$action_insert_stmt = $db_write->stmt_init();
				$action_insert_stmt->prepare("INSERT INTO actions (action_id, action_name) VALUES (NULL, ?)");
				$action_insert_stmt->bind_param_and_execute('s', $action['name']);
				$action['id'] = $action_insert_stmt->insert_id;
			}

			$insert_stmt = $db_write->stmt_init();
			$insert_stmt->prepare("INSERT INTO permissionprofile_has_actions (perprofile_id, action_id, permission_status) VALUES (?, ?, '0')");
			$insert_stmt->bind_param_and_execute('ii', $this->id, $action['id']);
		}



	}


}

class MemberGroupManager
{
	public $id;
	public $name;

	public function add_permission($profile_id, $location_id)
	{
		if(!isset($this->id))
			$this->load();

		$permission = new GroupPermission($this->id, $profile_id, $location_id);
		$permission->save();
	}

	public function remove_permission($profile_id, $location_id)
	{
		if(!isset($this->id))
			$this->load();

		$permission = new GroupPermission($this->id, $profile_id, $location_id);
		$permission->delete();
	}

	public function save_membergroup()
	{

		if(!isset($this->id))
			$this->load();


		if(!$this->id)
		{

			$db_write = db_connect('default');
			$insert_stmt = $db_write->stmt_init();
			$insert_stmt->prepare("INSERT INTO member_group (memgroup_id, memgroup_name) VALUES (NULL, ?)");
			$insert_stmt->bind_param_and_execute('s', $this->name);
			$this->id = $insert_stmt->insert_id;

		}else{

			$db_write = db_connect('default');
			$update_stmt = $db_write->stmt_init();
			$update_stmt->prepare("UPDATE member_group SET  memgroup_name = ? WHERE memgroup_id = ?");
			$update_stmt->bind_param_and_execute('si', $this->name, $this->id);

		}

	}

	public function add_user($user_id)
	{
		if($this->id <1)
			$this->load();
		$db_write = db_connect('default');
		$insert_stmt = $db_write->stmt_init();
		$insert_stmt->prepare("INSERT INTO user_in_member_group (user_id, memgroup_id) VALUES (?, ?)");
		$insert_stmt->bind_param_and_execute('ii', $user_id, $this->id);
	}

	public function remove_user($user_id)
	{
		if($this->id <1)
			$this->load();
		$db_write = db_connect('default');
		$insert_stmt = $db_write->stmt_init();
		$insert_stmt->prepare("DELETE FROM user_in_member_group WHERE user_id = ? AND memgroup_id = ?");
		$insert_stmt->bind_param_and_execute('ii', $user_id, $this->id);
	}

	public function load()
	{
		$db_read = db_connect('default_read_only');
		$select_stmt = $db_read->stmt_init();
		$select_stmt->prepare("SELECT memgroup_name, memgroup_id FROM member_group WHERE memgroup_name = ?");
		$select_stmt->bind_param_and_execute('s', $this->name);

		if($select_stmt->num_rows > 0)
		{
			$result = $select_stmt->fetch_array();
			$this->id = $result['memgroup_id'];
		}
	}
}

class GroupPermission
{
	public $member_group_id;
	public $permission_profile_id;
	public $location_id ;

	public function __construct($member_group_id, $permission_profile_id, $location_id)
	{
		$this->member_group_id = $member_group_id;
		$this->permission_profile_id = $permission_profile_id;
		$this->location_id = $location_id;
	}

	public function save()
	{
		$db_write = db_connect('default');
		$insert_stmt = $db_write->stmt_init();
		$insert_stmt->prepare("INSERT INTO group_permissions (memgroup_id, perprofile_id, location_id) VALUES (?, ?, ?)");
		$insert_stmt->bind_param_and_execute('iii', $this->member_group_id, $this->permission_profile_id, $this->location_id);
	}

	public function delete()
	{
		$db_write = db_connect('default');
		$insert_stmt = $db_write->stmt_init();
		$insert_stmt->prepare("DELETE FROM group_permissions WHERE memgroup_id = ? AND perprofile_id = ? AND location_id = ?");
		$insert_stmt->bind_param_and_execute('iii', $this->member_group_id, $this->permission_profile_id, $this->location_id);
	}
}

class UserPermission
{
	public $user_id;
	public $permission_profile_id;
	public $location_id;

	public function load($user_id, $permission_profile_id, $location_id)
	{
		$this->user_id = $user_id;
		$this->permission_profile_id = $permission_profile_id;
		$this->location_id = $location_id;
	}

	public function save()
	{
		$db_write = db_connect('default');
		$insert_stmt = $db_write->stmt_init();
		$insert_stmt->prepare('INSERT INTO user_permissions (user_id, perprofile_id, location_id) VALUES (?, ?, ?)');
		$insert_stmt->bind_param_and_execute('iii', $this->user_id, $this->permission_profile_id, $this->location_id);
	}

}

class ManageUser
{
	public $user_id;
	public $user_name;
	public $user_password;
	public $user_email;
	public $allow_login = false;
	public $membergroups = array();

	public function save()
	{
		$db_write = db_connect('default');
		$db_write->autocommit(false);


		$user_id = ($this->user_id) ? true : false;
		$password = isset($this->user_password);

		$allow_login = ($this->allow_login) ? '1' : '0';

		switch(true)
		{
			case (!$user_id && !$password):// NO ID, ADD USERNAME (not password)
				$insert_stmt = $db_write->stmt_init();
				$insert_stmt->prepare('INSERT INTO users (user_id, user_name, user_email, user_allowlogin)
					VALUES (NULL, ?, ?, ?)');
				$insert_stmt->bind_param_and_execute('ssi', $this->user_name, $this->user_email, $allow_login);
				$this->user_id = $insert_stmt->insert_id;
				break;


			case (!$user_id && $password): // NO ID, ADD USERNAME AND PASSWORD
				$store_password = new NewPassword($this->user_password);
				$insert_stmt = $db_write->stmt_init();
				$insert_stmt->prepare('INSERT INTO users (user_id, user_name, user_password, user_email, user_allowlogin)
							 VALUES (NULL, ?, ?, ?, ?)');
				$insert_stmt->bind_param_and_execute('sssi', $this->user_name, $store_password->stored,
							$this->user_email, $allow_login);
				$this->user_id = $insert_stmt->insert_id;
				unset($this->user_password);
				break;



			case ($user_id && !$password):	// HAS ID, UPDATE USERNAME (not password)
				$update_stmt = $db_write->stmt_init();
				$update_stmt->prepare('UPDATE users SET user_name = ?, user_email = ?, user_allowlogin = ?
										WHERE user_id = ?');
				$update_stmt->bind_param_and_execute('ssii', $this->user_name, $this->user_email, $allow_login,
									$this->user_id);
				break;

			case ($user_id && $password): // HAS USERID, UPDATE USERNAME AND PASSWORD
				$store_password = new NewPassword($this->user_password);
				$update_stmt = $db_write->stmt_init();
				$update_stmt->prepare('UPDATE users SET user_name = ?, user_password = ?, user_email = ?, user_allowlogin = ? WHERE user_id = ?');
				$update_stmt->bind_param_and_execute('sssii', $this->user_name, $store_password->stored, $this->user_email, $allow_login, $this->user_id);
				unset($this->user_password);
				break;

			default:
				break;
		}

		$delete_stmt = $db_write->stmt_init();
		$delete_stmt->prepare('DELETE FROM user_in_member_group WHERE user_id = ?');
		$delete_stmt->bind_param_and_execute('i', $this->user_id);

		foreach($this->membergroups as $id => $name)
		{
			$temp_membergroup = new MemberGroupManager();
			$temp_membergroup->id = $id;
			$temp_membergroup->add_user($this->user_id);
		}

		$db_write->commit();
		$db_write->autocommit(true);
	}

	public function load_by_id($id)
	{
		$this->load_by_field('user_id', $id);
	}

	protected function load_by_field($field, $id)
	{
		if($id > 0)
		{
			$db_read = db_connect('default_read_only');
			$select_user_stmt = $db_read->stmt_init();


			$results = $db_read->query('SELECT * FROM users WHERE ' . $field . ' = ' . $id);


			$results_user = $results->fetch_array();

			$this->user_id = $results_user['user_id'];
			$this->user_name = $results_user['user_name'];
			$this->user_email = $results_user['user_email'];
			$this->allow_login = ($results_user['user_allowlogin'] == 1);

			$this->load_membergroups();


		}
	}

	public function load_membergroups()
	{
		if($this->user_id > 0)
		{
			$db_read = db_connect('default_read_only');
			$select_membergroups_stmt = $db_read->stmt_init();
			$select_membergroups_stmt->prepare('SELECT member_group.memgroup_id, member_group.memgroup_name FROM member_group, user_in_member_group WHERE user_in_member_group.memgroup_id = member_group.memgroup_id AND user_in_member_group.user_id = ?');
			$select_membergroups_stmt->bind_param_and_execute('i', $this->user_id);

			while($results_memgroup = $select_membergroups_stmt->fetch_array())
			{
				$this->membergroups[$results_memgroup['memgroup_id']] = $results_memgroup['memgroup_name'];
			}
		}
	}

	public function delete()
	{

		if($this->user_id > 0)
		{
			// delete membergroup existance
			$db_write = db_connect('default');
			$delete_stmt = $db_write->stmt_init();
			$delete_stmt->prepare('DELETE FROM user_in_member_group WHERE user_id = ?');
			$delete_stmt->bind_param_and_execute('i', $this->user_id);
			// delete userpermission
			$delete_stmt = $db_write->stmt_init();
			$delete_stmt->prepare('DELETE FROM user_permissions WHERE user_id = ?');
			$delete_stmt->bind_param_and_execute('i', $this->user_id);
			// delete self
			$delete_stmt = $db_write->stmt_init();
			$delete_stmt->prepare('DELETE FROM users WHERE user_id = ?');
			$delete_stmt->bind_param_and_execute('i', $this->user_id);
		}
	}


}

class ManageLocation
{
	public $id = 0;
	public $parent;
	public $resource_type;
	public $name;


	public function __construct($id = 0)
	{
		if($id != 0)
		{
			$db = db_connect('default_read_only');
			$stmt = $db->stmt_init();
			$stmt->prepare("SELECT * FROM locations WHERE location_id = ?");
			$stmt->bind_param_and_execute('i', $id);

			$result = $stmt->fetch_array();

			$this->id = $result['location_id'];

			$this->parent = ($result['location_parent']) ? new Location($result['location_parent']) : false;
			$this->resource_type = $result['location_resource'];
			$this->name = $result['location_name'];
		}
	}

	public function load()
	{
		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$sql_set = false;

		if($this->id > 0)
		{
			$stmt->prepare("SELECT * FROM locations WHERE location_id = ?");
			$stmt->bind_param_and_execute('i', $this->id);
			$sql_set = true;
		}elseif($this->name){
			$stmt->prepare("SELECT * FROM locations WHERE location_name = ?");
			$stmt->bind_param_and_execute('i', $this->name);
			$sql_set = true;
		}

		if($sql_set && $stmt->num_rows > 0)
		{
			$result = $stmt->fetch_array();
			$this->id = $result['location_id'];
			$this->parent = ($result['location_parent']) ? new Location($result['location_parent']) : false;
			$this->resource_type = $result['location_resource'];
			$this->name = $result['location_name'];
		}

	}

	public function save()
	{
		$db = db_connect('default');

		$insert_stmt = $db->stmt_init();
		if($this->id != 0)
		{
			$db = db_connect('default');
			$insert_stmt->prepare("UPDATE locations SET location_parent = ? AND location_resource = ? AND location_name = ? WHERE location_id = ?");
			$insert_stmt->bind_param_and_execute('issi', ($this->parent) ? $this->parent->id : '', $this->resource_type, $this->name, $this->id);
		}else{


			$insert_stmt->prepare("INSERT INTO locations (location_id,  location_resource, location_name) VALUES (NULL,  ?, ?)");
			$parent_id = isset($this->parent) ? $this->parent->id : '1';

			$insert_stmt->bind_param_and_execute('ss', $this->resource_type, $this->name);
			$this->id = $insert_stmt->insert_id;


			$parent_id = isset($this->parent) ? $this->parent->id : '1';

			if(is_int($parent_id))
			{
				$sql = "UPDATE locations SET location_parent = $parent_id WHERE location_id = $this->id";
				$result = $db->query($sql);
				if($result)
				{

				}else{
					throw new BentoError('Unable to save location');
				}

			}

		}
	}

	public function parent_location()
	{
		if(!isset($this->parent))
			$this->load();

		return $this->parent;
	}

	public function location_id()
	{
		if(!$this->id > 0)
			$this->load();

		return $this->id;
	}

	public function resource_type()
	{
		if(!isset($this->resource_type))
			$this->load();

		return $this->resource_type;
	}

	public function name()
	{
		if(!isset($this->name))
			$this->load();

		return $this->name;
	}

	public function get_children_by_resource($resource = 'directory')
	{
		if(!isset($this->id))
			$this->load();

		$db = db_connect('default_read_only');
		$stmt = $db->stmt_init();
		$stmt->prepare('SELECT location_id, location_name FROM locations WHERE location_parent = ? AND location_resource = ?');
		$stmt->bind_param_and_execute('is', $this->id, $resource);

		$output = array();
		while($location_result = $stmt->fetch_array())
		{
			$output[$location_result['location_id']] = $location_result['location_name'];
		}

		return $output;
	}

}
?>