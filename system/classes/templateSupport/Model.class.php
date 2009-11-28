<?php

class TagBoxModel
{

	protected $model;
	protected $location;
	protected $modelArray;

	public function __construct(Model $model)
	{
		$this->model = $model;
		$this->location = method_exists($this->model, 'getLocation') ? $this->model->getLocation() : new Location(1);
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

		unset($modelArray['group']);
		unset($modelArray['rawContent']);
		
		$this->modelArray = $modelArray;
	}

	protected function getPermalink()
	{
		$url = new Url();
		$url->location = $this->location->getId();

		$query = Query::getQuery();
		$url->format = $query['format'];

		return (string) $url;
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

		$actionList = '';
		foreach($actionTypes as $action)
		{
			if (isset($allowedActions[$action])) {
				$actionUrl = $actionUrls[$action];
				$actionLink = $actionUrl->getLink(ucfirst($action));

				$modelListAction = new ViewStringTemplate("<li class='action action_$action'>{{ action }}</li>");

				$modelListAction->addContent(array('action' => $actionLink));
				$actionList .= $modelListAction->getDisplay();
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