<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage Dashboard
 */

/**
 * The ActionControl extends the ControlBase in order to enable the existence of Controls which simply wrap the output
 * of Mortar Actions. This abstract contains the code needed to load an action by name, pass a custom query to it, and
 * then return its value in a format compatible with the Control system.
 *
 * @package System
 * @subpackage Dashboard
 */
abstract class ActionControl extends ControlBase
{
	/**
	 * A custom Query array which is passed to the action. This string should generally include the action being
	 * performed as well as any specific parameters which this action should be passed. Generally set in advance
	 * but can be modified at runtime.
	 *
	 * @access protected
	 * @var array
	 */
	protected $customQuery = array();

	/**
	 * Loads and stores the current system query, constructs a new one based on the $customQuery property and
	 * the location, and then attempts to load, start, and view the specified action. If all that works, the
	 * resulting content is returned; otherwise, a text error message for the end user is returned instead.
	 * The original system query is restored right before this class returns either a success or an error.
	 *
	 * @return string
	 */
	public function getContent()
	{
		if($this->customQuery['action'] === 'Dashboard')
			return 'Cannot register the Dashboard as a control.';

		$iop = new IOProcessorHttp();
		$argument = '';

		$query = Query::getQuery();
		$oldquery = clone($query);

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
				Query::setQuery((array) $oldquery);
				return "There was an error loading the action for this control.";
			}

			$name = $actionInfo['className'];
			$action = new $name($actionInfo['argument'], $iop);
			$page = ActivePage::getInstance();

			try {
				$action->start();
				if(method_exists($action, 'viewControl')) {
					$result = $action->viewControl($page);
				} else {
					$result = $action->viewAdmin($page);
				}
			} catch (Exception $e) {
				Query::setQuery((array) $oldquery);
				return "You do not have permission to use this control.";
			}

			Query::setQuery((array) $oldquery);

			return $result;
		} else {
			Query::setQuery($oldquery);
			return "This control requires a location to be set.";
		}
	}

	/**
	 * This method returns an array of (className, argument) containing a string and Model for use in creating
	 * an Action object. NOTE: This is a very slightly altered port of functionality directly from the
	 * RequestWrapper and in general should probably be refactored at some point
	 *
	 * @return array
	 */
	protected function getActionClass()
	{
		$query = Query::getQuery();

		if(isset($query['location']))
			$locationId = $query['location'];

		if(is_numeric($query['location']))
			$location = Location::getLocation($query['location']);

		try {
			if(isset($query['module']))
			{
				$moduleInfo = PackageInfo::loadById($query['module']);

				if($moduleInfo->getStatus() != 'installed')
					return false;

				if(!isset($query['action']))
					$query['action'] = 'Default';

				if(!($actionInfo = $moduleInfo->getActions($query['action'])))
					return false;

				$argument = '';
				$className = $moduleInfo->getClassName('action', $actionInfo['name'], true);

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