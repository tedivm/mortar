<?php

class MortarPluginMenusAdminModels
{

	public function addModelMenuItems($menuSys, $model)
	{
		if(!method_exists($model, 'getLocation'))
			return false;

		$location = $model->getLocation();

		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = 'admin';

		$basicActions = staticHack(get_class($model), 'fallbackModelActions');
		$basicActionNames = staticHack(get_class($model), 'fallbackModelActionNames');
		$allowedActions = $model->getActions();
		$disallowedActions = array('Read', 'Add', 'EditGroupPermissions', 'EditUserPermissions', 'ThemePreview');
		$modelActions = array();

		foreach($basicActions as $action) {
			$actionName = isset($basicActionNames[$action]) ? $basicActionNames[$action] : $action;
			if ((isset($allowedActions[$action])) && !(in_array($action, $disallowedActions)))
				$modelActions[$action] = $actionName;
		}

		if($location->hasChildren())
			$modelActions['Index'] = 'Browse';

		foreach($modelActions as $action => $label)
		{
			if($model->getAction($action) == false)
				continue;

			$browseUrl = clone $url;
			$browseUrl->action = $action;
			$link = $browseUrl->getLink($label);
			$menuSys->addItemToSubmenu('secondary', $model->getType(), $link, $label, 0, $browseUrl);
		}

		$listOfAllowedModels = $model->getAllowedChildrenTypes();

		$addUrlBase = clone $url;
		$addUrlBase->action = 'Add';

		foreach($listOfAllowedModels as $modelType)
		{
			$addUrl = clone $addUrlBase;
			$addUrl->type = $modelType;
			$link = $addUrl->getLink($modelType);
			$menuSys->addItemToSubmenu('secondary', 'Add', $link, $modelType, 0, $addUrl);
		}
	}
}

?>