<?php

class MortarActionModulePermissions extends ActionBase
{

	public static $requiredPermission = 'Admin';
	
	public $adminSettings = array( 'headerTitle' => 'Module Permissions', 'useRider' => true );
	
	protected $formName = 'ModulePermissions';

	protected $module;
	protected $moduleName;
	protected $models;

	protected function logic()
	{
		$query = Query::getQuery();

		if ( (!isset($query['id'])) || (!is_numeric($query['id'])) ) {
			$this->redirectAway();
			return false;
		}

		$this->module = $query['id'];

		$packageInfo = new PackageInfo($this->module);

		$this->moduleName = $packageInfo->getName();
		$this->models = $packageInfo->getModels();

		$this->adminSettings['titleRider'] = ' for ' . $this->moduleName;

		if( count($this->models) === 0 ) {
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
		$form = new Form($this->formName . '_' . $this->moduleName);

		$actionList = PermissionActionList::getActionList();

		$memberGroupRecords = new ObjectRelationshipMapper('memberGroup');
		$memberGroupRecords->is_system = 0;
		$memberGroupRecords->select();
		$memgroups = $memberGroupRecords->resultsToArray();
		$membergroups = array();

		foreach($memgroups as $group) {
			if($group['memgroup_name'] === 'Guest')
				$guestGroup = $group;
			else
				$membergroups[] = $group;
		}
		$membergroups[] = $guestGroup;

		foreach($this->models as $model) {
			$form->changeSection('model_' . $model['name'])->
				setLegend($model['name']);

			foreach($membergroups as $group) {
				$x = 0;
				$first = true;
				$last = false;

				foreach($actionList as $action) {
					if(++$x === count($actionList))
						$last = true;

					$input = $form->createInput($model['name'] . '_' . $group['memgroup_name'] . 
						'_' . $action)->
						setType('checkbox')->
						setLabel($action);

					if( ($group['memgroup_name'] === 'Administrator') || ($action === 'Read') )
						$input->check(1);

					if($first) {
						$input->setPretext("<fieldset><legend>".
							$group['memgroup_name']."</legend>");
						$first = false;
					}

					if($last) {
						$input->setPosttext("</fieldset>");
						$input->noBreak(true);
					}
				}
			}
		}

                return $form;
	}

	protected function processInput($input)
	{
		$site = ActiveSite::getSite();
		$location = $site->getLocation();

		$actionList = PermissionActionList::getActionList();

		$memberGroupRecords = new ObjectRelationshipMapper('memberGroup');
		$memberGroupRecords->is_system = 0;
		$memberGroupRecords->select();
		$membergroups = $memberGroupRecords->resultsToArray();

		foreach($membergroups as $group)
			$permissions[$group['memgroup_name']] = new GroupPermission($location, $group['memgroup_id']);

		foreach($this->models as $model)
			foreach($membergroups as $group)
				foreach($actionList as $action)
					if(isset($input[$model['name'].'_'.$group['memgroup_name'].'_'.$action]))
						$permissions[$group['memgroup_name']]->
							setPermission($model['name'], $action, true);

		foreach($permissions as $perm)
			$perm->save();

		$url = new Url();
		$url->module = 'Mortar';
		$url->action = 'InstallModule';
		$url->format = 'admin';
		$this->ioHandler->addHeader('Location', (string) $url);
	}

	protected function redirectAway()
	{
		$url = Query::getUrl();
		$url->action = 'Read';
		unset($url->module);
		$this->ioHandler->addHeader('Location', (string) $url);
	}

	public function viewAdmin()
	{
		$this->setTitle($this->adminSettings['headerTitle'] . $this->adminSettings['titleRider']);

		return $this->form->getFormAs('Html');
	}

}

?>