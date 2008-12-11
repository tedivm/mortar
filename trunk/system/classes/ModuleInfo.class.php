<?php

class ModuleInfo implements ArrayAccess
{
	protected $modId;
	public $settings;
	protected $info;

	public function __construct($id, $loadBy = '')
	{
		if(!is_numeric($id))
			return;

		$cacheInfo = new Cache('modules', $id, 'info');
		$config = Config::getInstance();
		$info = $cacheInfo->get_data();

		if(!$cacheInfo->cacheReturned)
		{
			$infoRow = new ObjectRelationshipMapper('modules');

			switch ($loadBy)
			{
				case 'name':
					$infoRow->mod_name = $id;
					break;

				case 'location':
					$infoRow->location_id = $id;
					break;
				case 'id':
				default:
					$infoRow->mod_id = $id;
					break;
			}

			if($infoRow->select())
			{
				$location = new Location($infoRow->location_id);
				$info['Name'] = $location->getName();
				$info['ID'] = $infoRow->mod_id;
				$info['Package'] = $infoRow->mod_package;
				$info['PathToPackage'] = $config['path']['modules'] . $info['Package'] . '/';
				$info['locationId'] = $infoRow->location_id;
				$location = new Location($infoRow->location_id);
				$info['siteId'] = $location->siteId;
			}

			$settingsInfoRow = new ObjectRelationshipMapper('mod_config');
			$settingsInfoRow->mod_id = $info['id'];
			$settingsInfoRow->select();

			do{
				$settings[$settingsInfoRow->name] = $settingsInfoRow->value;
			}while($settingsInfoRow->next());

			$info['settings'] = $settings;
			$cacheInfo->store_data($info);
		}

		$this->info = $info;
		$this->modId = $this->info['ID'];
		$this->settings = $info['settings'];
	}

	public function checkAuth($action)
	{
		if(!($this->permissions instanceof Permissions))
		{
			$user = ActiveUser::getInstance();
			$location = new Location($this->info['locationId']);
			$this->permission = new Permissions($location, $user);
			//echo 1;
		}
	//	var_dump($this->permission);
		//echo $action;

		return $this->permission->is_allowed($action);
	}

	public function settings()
	{
		return $this->settings;
	}

	public function getId()
	{
		return $this->modId;
	}

	public function setting($name)
	{
		return $this->settings[$name];
	}

	public function offsetGet($offset)
	{
		return $this->info[$offset];
	}
	public function offsetSet($offset, $value)
	{
		return ($this->info[$offset] = $value);
	}
	public function offsetUnset($offset)
	{
		unset($this->info[$offset]);
	}
	public function offsetExists($offset)
	{
		return isset($this->info[$offset]);
	}



}

?>
