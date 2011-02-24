<?php

class MortarCoreActionModulePermissions extends ActionBase
{

	public static $requiredPermission = 'Admin';

	public static $settings = array( 'Base' =>
		array( 'headerTitle' => 'Module Permissions', 'useRider' => true ) );

	protected $formName = 'ModulePermissions';

	protected $module;
	protected $moduleName;
	protected $models;

	protected function logic()
	{
		$query = Query::getQuery();

		if(!isset($query['id']) || !is_numeric($query['id']))
		{
			$this->redirectAway();
			return false;
		}

		$this->module = $query['id'];

		$packageInfo = PackageInfo::loadById($query['id']);

		$this->moduleName = $packageInfo->getName();
		$this->models = $packageInfo->getModels();

		$this->setSetting('titleRider', 'Base', ' for ' . $this->moduleName);

		if(count($this->models) === 0 )
		{
			$this->redirectAway();
			return false;
		}

		$this->form = $this->getForm();

		if($this->form->checkSubmit())
		{
			$this->formStatus = ($this->processInput($this->form->getInputHandler()));
		}
	}

	protected function getForm()
	{
		$form = new MortarFormForm($this->formName . '_' . $this->moduleName);

		$actionList = PermissionActionList::getActionList();

		$site = ActiveSite::getSite();
		$location = $site->getLocation();
		$query = Query::getQuery();

		$memberGroupRecords = new ObjectRelationshipMapper('memberGroup');
		$memberGroupRecords->select();
		$memgroups = $memberGroupRecords->resultsToArray();
		$membergroups = array();
		$pList = array();
		$aList = PermissionActionList::getActionList(false);

		foreach($memgroups as $group)
		{
			if($group['memgroup_name'] === 'Guest')
			{
				$guestGroup = $group;
			}elseif($group['is_system'] == 0){
				$membergroups[] = $group;
			}

			$p = new GroupPermission($location, $group['memgroup_id']);
			$pList[$group['memgroup_name']] = $p->getPermissionsList();
		}

		if(isset($guestGroup))
			$membergroups[] = $guestGroup;

		foreach($this->models as $model)
		{
			$form->changeSection('model_' . $model['name'])->
				setLegend($model['name'])->
				setSectionOutro('<div class="clear-fieldsets"></div>');

			foreach($membergroups as $group)
			{
				$x = 0;
				$first = true;
				$last = false;

				foreach($actionList as $action)
				{
					if(++$x === count($actionList))
						$last = true;

					$input = $form->createInput($model['name'] . '_' . $group['memgroup_name'] .
						'_' . $action)->
						setType('checkbox')->
						setLabel($action);

					if($query['first'] === 'yes') {
						if(($group['memgroup_name'] === 'Administrator')
							|| ($action === 'Read'))
						{
							$input->check(1);
						}
					}

					if($query['first'] !== 'yes') {
						$g = $group['memgroup_name'];
						$m = $model['name'];
						$a = $aList[$action];
						if(isset($pList[$g][$m][$a]) && $pList[$g][$m][$a] === true) {
							$input->check(1);
						}
					}

					if($first)
					{
						$input->setPretext('<fieldset><legend>' . $group['memgroup_name']."</legend>");
						$first = false;
					}

					if($last)
					{
						$input->setPosttext('</fieldset>');
						$input->noBreak(true);
					}
				} //foreach($actionList as $action)
			} // foreach($membergroups as $group)
		} // foreach($this->models as $model)

		return $form;
	}

	protected function processInput($input)
	{
		$site = ActiveSite::getSite();
		$location = $site->getLocation();

		$actionList = PermissionActionList::getActionList();

		$memberGroupRecords = new ObjectRelationshipMapper('memberGroup');
		$memberGroupRecords->select();
		$membergroups = $memberGroupRecords->resultsToArray();

		foreach($membergroups as $group)
			$permissions[$group['memgroup_name']] = new GroupPermission($location, $group['memgroup_id']);

		foreach($this->models as $model)
			foreach($membergroups as $group)
				foreach($actionList as $action)
		{
			if(isset($input[$model['name'].'_'.$group['memgroup_name'].'_'.$action]))
			{
				$permissions[$group['memgroup_name']]->
					setPermission($model['name'], $action, true);
			} else {
				$permissions[$group['memgroup_name']]->
					setPermission($model['name'], $action, 'unset');
			}
		}

		foreach($permissions as $perm)
			$perm->save();

		$url = new Url();
		$url->module = PackageInfo::loadByName('Mortar', 'Core');
		$url->action = 'InstallModule';
		$url->format = 'admin';
		$this->ioHandler->addHeader('Location', (string) $url);
	}

	protected function redirectAway()
	{
		$url = Query::getUrl();
		$url->action = 'InstallModule';
		unset($url->id);
		$this->ioHandler->addHeader('Location', (string) $url);
	}

	public function viewAdmin($page)
	{
		if(isset($this->form)) {
			return $this->form->getFormAs('Html');
		} else {
			return false;
		}
	}

}

?>