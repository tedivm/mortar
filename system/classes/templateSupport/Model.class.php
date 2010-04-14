<?php

class TagBoxModel
{

	protected $model;
	protected $modelArray;

	protected $jump = '<!-- jump -->';
	protected $jumpPhrase = 'Read more...';
	protected $jumpClass = 'readmore';

	public function __construct(Model $model)
	{
		$this->model = $model;
		$modelArray = $model->__toArray(); 

		if(isset($modelArray['name'])) {
			$modelArray['name'] = ucwords(str_replace('_', ' ', $modelArray['name']));
		}

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

		$hook = new Hook();
		$hook->loadPlugins('model', 'All', 'toArray');
		$pluginArray = Hook::mergeResults($hook->toArray($model));
		$modelArray = array_merge($pluginArray, $modelArray);

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

	public function content($pars = null)
	{
		if(!isset($this->modelArray['content']))
			return false;

		$place = strpos($this->modelArray['content'], $this->jump);

		if($place !== false) {
			$breakAt = $place;
		} elseif(isset($pars) && is_numeric($pars)) {
			$start = 0;
			for ($i = 1; $i <= $pars; $i++) {
				$pos = strpos($this->modelArray['content'], '</p>', $start);
				if($pos !== false) {
					$start = $pos + 4;
				} else {
					$start = false;
					break;
				}
			}

			if($start && is_numeric($start)) {
				$breakAt = $start;
			}
		}

		if(isset($breakAt)) {
			$prejump = substr($this->modelArray['content'], 0, $breakAt);
			$postjump = substr($this->modelArray['content'], $breakAt);
			$a = new HtmlObject('a');
			$a->property('name', 'continue');
			return $prejump . (string) $a . $postjump;
		} else {
			return $this->modelArray['content'];
		}
	}

	public function shortContent($pars = null, $jumptext = null, $jumpclass = null)
	{
		if(!isset($this->modelArray['content']))
			return false;

		$place = strpos($this->modelArray['content'], $this->jump);

		$jumpclass = isset($jumpclass) ? $jumpclass : $this->jumpClass;
		$jumptext = isset($jumptext) ? $jumptext : $this->jumpPhrase;

		$url = $this->model->getUrl();
		$rlink = new HtmlObject('a');
		$rlink->property('href', ((string) $url) . '#continue');
		$rlink->wrapAround($jumptext);
		$link = new HtmlObject('div');
		$link->addClass($jumpclass);
		$link->wrapAround($rlink);

		$content = '';

		$pur = new HTMLPurifier();

		if($place !== false) {
			$prejump = substr($this->modelArray['content'], 0, $place);
			$content = $pur->purify($prejump) . $link;
		} elseif(isset($pars) && is_numeric($pars)) {
			$start = 0;
			for ($i = 1; $i <= $pars; $i++) {
				$pos = strpos($this->modelArray['content'], '</p>', $start);
				if($pos !== false) {
					$start = $pos + 4;
				} else {
					$start = false;
					break;
				}
			}

			if(isset($start)) {
				$trim = substr($this->modelArray['content'], 0, $start);
				if(trim($trim) === trim($this->modelArray['content'])) {
					$content = $this->modelArray['content'];
				} else {
					$content = $pur->purify($trim) . $link;
				}
			}
		} else {
			$content = $this->modelArray['content'];
		}

		return $content;
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
			case 'content':
				return false;
		}
		return isset($this->modelArray[$key]);
	}
}

?>