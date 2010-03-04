<?php

abstract class ActionControl extends ControlBase
{
	protected $customQuery = array();

	public function getContent()
	{
		if($this->customQuery['action'] === 'Dashboard')
			return 'Cannot register the Dashboard as a control.';

		$iop = new IOProcessorHttp();
		$argument = '';

		$oldquery = Query::getQuery();
		$query = Query::getQuery();
		foreach(array('module', 'action', 'location') as $key) {
			if(isset($query[$key])) {
				unset($query[$key]);
			}
		}		
		
		if($this->useLocation && $this->location)
		{
			$query['location'] = $this->location;
		}

		foreach($this->customQuery as $key => $value) {
			$query[$key] = $value;
		}

		Query::setQuery($query);

		if ($this->useLocation === false || ($this->useLocation && $this->location)) {
			$actionInfo = $this->getActionClass();
			if($actionInfo === false) {
				Query::setQuery($oldquery);
				return "There was an error loading the action for this control.";
			}

			$name = $actionInfo['className'];
			$action = new $name($actionInfo['argument'], $iop);
			$page = ActivePage::getInstance();

			try {
				$action->start();
				$result = $action->viewAdmin($page);
			} catch (Exception $e) {
				Query::setQuery($oldquery);
				return "You do not have permission to use this control.";
			}

			Query::setQuery($oldquery);

			return $result;
		} else {
			Query::setQuery($oldquery);
			return "This control requires a location to be set.";
		}
	}
	
	protected function getActionClass()
	{
		$query = Query::getQuery();

		if(isset($query['location']))
			$locationId = $query['location'];

		if(is_numeric($query['location']))
			$location = new Location($query['location']);

		try {
			if($query['module'])
			{
				$moduleInfo = new PackageInfo($query['module']);

				if($moduleInfo->getStatus() != 'installed')
					return false;

				if(!isset($query['action']))
					$query['action'] = 'Default';

				if(!($actionInfo = $moduleInfo->getActions($query['action'])))
					return false;

				$argument = '';
				$className = importFromModule($actionInfo['name'], $query['module'], 'action', true);

				$query->save();
				return array('className' => $className, 'argument' => $argument);
			}

		}catch(Exception $e){
			return false;
		}


		if($query['action'] != 'Add')
		{
			if(!isset($location) && isset($query['type']))
			{
				if(!$model = ModelRegistry::loadModel($query['type'], $query['id']))
					return false;
			}else{
				if(!isset($location))
				{
					$site = ActiveSite::getSite();
					$location = $site->getLocation();
				}
				$model = $location->getResource();
			}

			if(!isset($query['action']))
				$query['action'] = 'Read';

			$actionInfo = $model->getAction($query['action']);
			if($actionInfo == false)
				return false;

			$className = $actionInfo['className'];
			$argument = $model;
			$query->save();

			return array('className' => $className, 'argument' => $argument);
		}

		if(isset($query['type']))
		{
			$type = $query['type'];
		}elseif(isset($location)){
			$parentModel = $location->getResource();
			if(!($type = $parentModel->getDefaultChildType()))
			{
				return false;
			}
		}else{
			return false;
		}

		$modelHandler = ModelRegistry::loadModel($type);
		if(!$modelHandler)
			return false;

		$model = new $modelHandler();
		$actionInfo = $model->getAction('Add');
		$className = $actionInfo['className'];
		$argument = $model;

		if(!class_exists($className))
			return false;

		if(($model instanceof LocationModel) && isset($location))
			$model->setParent($location);

		$query->save();
		return array('className' => $className, 'argument' => $argument);
	}
}

?>