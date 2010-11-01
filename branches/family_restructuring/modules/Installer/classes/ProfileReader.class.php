<?php

class InstallerProfileReader
{
	static $path;

	protected $aliases = array();
	protected $modules;
	protected $membergroups;
	protected $users;
	protected $locationTree;

	public function loadProfile($name)
	{
		try
		{
			$xml = $this->getXmlFromFile($name);
			$xml = new SimpleXMLElement($xml);
			$xml = $xml->profile;


			if(isset($xml['extends']))
			{
				$parentProfile = new InstallerProfileReader();

				if(!$parentProfile->loadProfile($xml['extends']))
					throw new CoreError('Unable to load parent profile ' . $xml['extends']);

				if($parentAliases = $parentProfile->getAliases())
					$this->aliases = array_merge($parentAliases, $this->getAliases());
			}

			if(isset($xml->aliases))
				$this->loadAliases($xml->aliases);

			if(isset($xml->modules->module))
				$this->loadModuleInformation($xml->modules->module);

			if(isset($xml->membergroups))
				$this->loadMemberGroupInformation($xml->membergroups);

			if(isset($xml->users->user))
				$this->loadUserInformation($xml->users->user);

			if(isset($xml->locations->location))
				$this->loadLocationInfo($xml->locations->location);

			if(isset($xml['extends']) && isset($parentProfile))
			{

				if($parentModules = $parentProfile->getModules())
					$this->modules = array_merge($parentModules, $this->getModules());

				if($parentMembergroups = $parentProfile->getMembergroups())
					$this->membergroups = array_merge($parentMembergroups, $this->getMembergroups());

				if($parentUsers = $parentProfile->getUsers())
					$this->users = array_merge($parentUsers, $this->getUsers());

				if($parentLocationTree = $parentProfile->getLocations())
					$this->locationTree = array_merge($parentLocationTree, $this->getLocations());
			}

		}catch(Exception $e){
			if($e instanceof CoreError)
				throw $e;

			throw new CoreError('Unable to load profile: ' . $e->getMessage());
		}
		return true;
	}

	public function getAliases()
	{
		return (isset($this->aliases)) ? $this->aliases : array();
	}

	public function getModules()
	{
		return (isset($this->modules)) ? $this->modules : array();
	}

	public function getMembergroups()
	{
		return (isset($this->membergroups)) ? $this->membergroups : array();
	}

	public function getUsers()
	{
		return (isset($this->users)) ? $this->users : array();
	}

	public function getLocations()
	{
		return (isset($this->locationTree)) ? $this->locationTree : array();
	}

	protected function loadAliases(SimpleXMLElement $xml)
	{
		$aliasTypes = array('modelGroup' => 'modelGroups',
							'actionGroup' => 'actionGroups');

		foreach($aliasTypes as $xmlLabel => $profileLabel)
		{
			if(!isset($xml->$xmlLabel))
				continue;

			foreach($xml->$xmlLabel as $modelGroup)
			{
				if(!isset($modelGroup->alias))
					continue;

				$name = (string) $modelGroup['name'];
				foreach($modelGroup->alias as $model)
				{
					$modelName = (string) $model;
					if(!isset($model['include']) || $model['include'] != 'false')
					{
						$this->aliases[$profileLabel][$name][] = $modelName;
					}elseif(in_array($modelName, $this->aliases[$profileLabel][$name])){
						$index = array_search($modelName, $this->aliases[$profileLabel][$name]);
						unset($this->aliases[$profileLabel][$index]);
					}
				}
			}
		}
	}

	protected function loadModuleInformation(SimpleXMLElement $xml)
	{
		foreach($xml as $module)
		{
			$family = isset($module['family']) ? (string) $module['family'] : 'orphan';
			$modules[$family][(string) $module['name']]['install'] = true;
		}

		$this->modules = $modules;
	}

	protected function loadMemberGroupInformation(SimpleXMLElement $xml)
	{
		$groups = array();
		foreach($xml->system->group as $memberGroup)
			$groups['system'][] = (string) $memberGroup;

		foreach($xml->user->group as $memberGroup)
			$groups['user'][] = (string) $memberGroup;

		$this->membergroups = $groups;
	}

	protected function loadUserInformation(SimpleXMLElement $xml)
	{
		$users = array();
		foreach($xml as $user)
		{
			$name = (string) $user['name'];
			$users[$name]['login'] = (isset($user['login']) && $user['login'] == 'true');
			$users[$name]['form'] = (isset($user['form']) && $user['form'] == 'true');

			foreach($user->group as $group)
				$users[$name]['groups'][] = (string) $group;
		}
		$this->users = $users;
	}

	protected function loadLocationInfo(SimpleXMLElement $xml)
	{
		$locations = $this->getLocationInfoFromXml($xml);
		$this->locationTree = $locations;
	}

	protected function getLocationInfoFromXml(SimpleXMLElement $xml, $parentName = null)
	{
		$locations = array();

		if(isset($parentName))
		{
			$parentName .= '_';
		}else{
			$parentName = '';
		}

		foreach($xml as $location)
		{
			$locationName = (string) $location['name'];

			$locations[$locationName]['name'] = $locationName;
			$locations[$locationName]['longname'] = $parentName . $locationName;
			$locations[$locationName]['type'] = (string) $location['type'];

			if(isset($location['inherit']))
			{
				$inheritsValue = (string) $location['inherit'];
				$inheritsValue = $inheritsValue != 'false';
			}else{
				$inheritsValue = true;
			}
			$locations[$locationName]['inherits'] = $inheritsValue;

			if(isset($location['form']))
			{
				$formValue = (string) $location['form'];
				$formValue = $formValue == 'true';
			}else{
				$formValue = false;
			}
			$locations[$locationName]['form'] = $formValue;


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


			if(isset($location['id']))
				$locations[$locationName]['id'] = (string) $location['id'];

			if(isset($location->content))
				foreach($location->content as $content)
				{
					$contentName = isset($content['name']) ? (string) $content['name'] : 'content';
					$locations[$locationName]['content'][$contentName] = (string) $content;
				}

			if(isset($location->property))
				foreach($location->property as $property)
				{
					if(!isset($property['name']))
						continue;

					$propertyName = (string) $property['name'];
					$locations[$locationName]['property'][$propertyName] = (string) $property;
				}

			if(isset($location->function))
				foreach($location->function as $function)
				{
					$functionInfo = array();
					$functionInfo['name'] = (string) $function['name'];

					if(isset($function->param))
						foreach($function->param as $parameter)
							$functionInfo['params'][] = array('form' => isset($parameter['form']), 'value' => (string) $parameter);

					$locations[$locationName]['functions'][] = $functionInfo;
				}

			if(isset($location->permissions))
				foreach($location->permissions->permission as $permission)
				{
					$processedPermission = array();

					foreach($permission->resources->resourceGroup as $resource)
						$processedPermission['resources'][] = (string) $resource;

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
					$locations[$locationName]['children'] = $this->getLocationInfoFromXml($location->children->location,
																			$locations[$locationName]['longname']);

		}
		return $locations;
	}

	protected function getXmlFromFile($fileName)
	{
		$path = self::$path . $fileName . '.xml';
		if(file_exists($path) && is_readable($path))
		{
			$xml = file_get_contents($path);
			return $xml;
		}else{
			throw new CoreError('No such installation profile at location ' . $path);
		}
		return false;
	}
}

?>