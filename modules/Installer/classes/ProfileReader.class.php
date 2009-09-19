<?php

class InstallerProfileReader
{
	protected $modules;
	protected $membergroups;
	protected $users;
	protected $locationTree;

	public function loadProfile($xml)
	{
		try
		{
			$xml = new SimpleXMLElement($xml);
			$this->modules = $this->getModuleInformation($xml->modules->module);
			$this->membergroups = $this->getMemberGroupInformation($xml->membergroups);
			$this->users = $this->getUserInformation($xml->users->user);
			$this->locationTree = $this->getLocationInfo($xml->locations->location);
		}catch(Exception $e){
			return false;
		}
		return true;
	}

	protected function getModuleInformation(SimpleXMLElement $xml)
	{
		foreach($xml as $module)
			$modules[(string) $module['name']]['install'] = true;

		return $modules;
	}

	protected function getMemberGroupInformation(SimpleXMLElement $xml)
	{
		$groups = array();
		foreach($xml->system->group as $memberGroup)
			$groups['system'][] = (string) $memberGroup;

		foreach($xml->user->group as $memberGroup)
			$groups['user'][] = (string) $memberGroup;

		return $groups;
	}

	protected function getUserInformation(SimpleXMLElement $xml)
	{
		$users = array();
		foreach($xml as $user)
		{
			$name = (string) $user['name'];
			$users[$name]['login'] = (isset($user['login']) && $user['login'] == 'true');

			foreach($user->group as $group)
				$users[$name]['groups'][] = (string) $group;
		}

		return $users;
	}

	protected function getLocationInfo(SimpleXMLElement $xml)
	{
		$locations = array();
		foreach($xml as $location)
		{
			$locationName = (string) $location['name'];


			if(isset($location->option))
			{
				foreach($location->option as $option)
					$options[(string) $option['name']] = (string) $option;

				$locations[$locationName]['options'] = $options;
			}

			if(isset($location->membergroup))
					$locations[$locationName]['group'] = (string) $location->membergroup;

			if(isset($location->user))
					$locations[$locationName]['user'] = (string) $location->user;


			$locations[$locationName]['type'] = (string) $location['type'];
			$locations[$locationName]['form'] = (isset($location['form']) && $location['form'] == 'true');

			if(isset($location['id']))
				$locations[$locationName]['id'] = (string) $location['id'];


			if(isset($location->permissions))
				foreach($location->permissions->permission as $permission)
				{
					$processedPermission = array();

					foreach($permission->resources->resource as $resource)
						$processedPermission['resources'][] = (string) $resource;

					foreach($permission->actions->action as $action)
						$processedPermission['actions'][] = (string) $action;

					if(isset($permission->groups))
						foreach($permission->groups->group as $group)
							$processedPermission['groups'][] = (string) $group;


					if(isset($permission->users))
						foreach($permission->users->user as $user)
							$processedPermission['users'][] = (string) $user;

					$locations[$locationName]['permissions'][] = $processedPermission;
				}

			if(isset($location->children))
				foreach($location->children as $children)
				{
					$locations[$locationName]['children'][] = $this->getLocationInfo($children);
				}
		}

		return $locations;
	}

}

?>