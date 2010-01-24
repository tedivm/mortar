<?php

class TagBoxModel
{

	protected $model;
	protected $modelArray;

	public function __construct(Model $model)
	{
		$this->model = $model;
		$modelArray = $model->__toArray();

		if(isset($modelArray['properties']))
		{
			$props = $modelArray['properties'];
			unset($modelArray['properties']);
			array_merge($modelArray, $props);
		}

		if(isset($modelArray['owner']))
			if(isset($modelArray['owner']['name']))
				$modelArray['owner'] = $modelArray['owner']['name'];
			else
				unset($modelArray['owner']);

		if(isset($modelArray['membergroups']))
			$modelArray['membergroups'] = $this->formatGroups($modelArray['membergroups']);

		unset($modelArray['group']);
		unset($modelArray['rawContent']);
		
		$this->modelArray = $modelArray;
	}

	protected function getPermalink()
	{
		return $this->model->getUrl();
	}

	protected function formatGroups($groups)
	{
		$first = true;
		$groupList = '';

		foreach($groups as $groupId) {
			$group = ModelRegistry::loadModel('MemberGroup', $groupId);

			if (!$first) 
				$groupList .= ', ';
			else
				$first = false;
			
			$groupList .= '<a href="' . $group->getUrl() . '">' . $group['name'] . '</a>';
		}
		
		return $groupList;
	}

	protected function getActionList()
	{
		$query = Query::getQuery();
		$user = ActiveUser::getUser();

		$actionUrls = $this->model->getActionUrls($query['format']);
		$allowedActions = $this->model->getActions($user);

		$actionTypes = array();
		foreach(array('Read', 'Edit', 'Delete', 'Index') as $action) 
			if(isset($allowedActions[$action]))
				array_push($actionTypes, $action); 

		if(!method_exists($this->model, 'getLocation'))
			$allowedActions = array_pop($allowedActions);

		$actionList = '';
		foreach($actionTypes as $action)
		{
			if (isset($allowedActions[$action])) {
				$actionUrl = $actionUrls[$action];
				$actionLink = $actionUrl->getLink(ucfirst($action));

				$modelListAction = "<li class='action action_$action'>$actionLink</li>";

				$actionList .= $modelListAction;
			}
		}
		
		return $actionList;
	}
	
	public function __get($key)
	{
		switch($key) {
			case 'permalink':
				return $this->getPermalink();
			case 'actionList':
				return $this->getActionList();
		}
		if(isset($this->modelArray[$key]))
			return $this->modelArray[$key];
		
		return false;
	}
	
	public function __isset($key)
	{
		switch($key) {
			case 'permalink':
			case 'actionList':
				return true;
		}
		return isset($this->modelArray[$key]);
	}
}

?>