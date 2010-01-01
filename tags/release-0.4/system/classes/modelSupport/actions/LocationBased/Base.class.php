<?php
/**
 * Mortar
 *
 * @copyright Copyright (c) 2009, Robert Hafner
 * @license http://www.mozilla.org/MPL/
 * @package System
 * @subpackage ModelSupport
 */

/**
 * This is the base class for all the other model based actions. It handles loading various information, checking
 * permissions, and setting cache headers based on the models age.
 *
 * @abstract
 * @package System
 * @subpackage ModelSupport
 */
abstract class ModelActionLocationBasedBase extends ModelActionBase
{
	protected $lastModified;

	/**
	 * This is the model that called up the action.
	 *
	 * @access protected
	 * @var LocationModel
	 */
	protected $model;

	/**
	 * This function sends along the Last-Modified headers, and if $this->cacheExpirationOffset is set it also sends
	 * that to the ioHandler. This is vital for client side http caching
	 *
	 * @access protected
	 */
	protected function setHeaders()
	{
		$location = $this->model->getLocation();
		$modifiedDate = $location->getLastModified();

		$locationListing = new LocationListing();
		$locationListing->addRestriction('parent', $this->model->getLocation()->getId());
		$locationListing->setOption('order', 'DESC');
		$locationListing->setOption('browseBy', 'lastModified');

		if($listingArray = $locationListing->getListing(1))
		{
			$childLocationInfo = array_pop($listingArray);
			$childModel = ModelRegistry::loadModel($childLocationInfo['type'], $childLocationInfo['id']);
			$location = $childModel->getLocation();
			$childModifiedDate = $location->getLastModified();
			if($childModifiedDate > $modifiedDate)
				$modifiedDate = $childModifiedDate;
		}


		if(isset($this->lastModified))
		{
			$locationModifiedDate = $location->getLastModified();
			if($this->lastModified > $modifiedDate)
			{
				$modifiedDate = $locationModifiedDate;
			}
		}

		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', $modifiedDate));

		if(isset($this->cacheExpirationOffset) && !isset($this->ioHandler->cacheExpirationOffset))
			$this->ioHandler->cacheExpirationOffset = $this->cacheExpirationOffset;
	}

	/**
	 * This function adds new actions and settings to a menu based off of the passed model.
	 *
	 * @plugin model actionMenu
	 * @param NavigationMenu $menu
	 * @param LocationModel $model
	 * @param string $format
	 */
	protected function makeModelActionMenu($menu, $model, $format = 'Html')
	{
		$menu->setMenu('models_actions');
		$menu->setMenuLabel($model->getType());

		$plugins = new Hook();
		$plugins->loadModelPlugins($model, 'ActionMenu');
		$plugins->addToMenu($menu, $model, $format);

		$location = $model->getLocation();

		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = $format;

		$basicActions = staticHack(get_class($model), 'fallbackModelActions');
		$basicActionNames = staticHack(get_class($model), 'fallbackModelActionNames');
		$allowedActions = $model->getActions();
		$disallowedActions = array('Read', 'Add', 'EditGroupPermissions', 'EditUserPermissions');
		$modelActions = array();

		foreach($basicActions as $action) {
			$actionName = isset($basicActionNames[$action]) ? $basicActionNames[$action] : $action;
			if ((isset($allowedActions[$action])) && !(in_array($action, $disallowedActions)))
				$modelActions[$action] = $actionName;
		}

		if($location->hasChildren())
			$modelActions['Index'] = 'Browse';

		$extraActions = Hook::mergeResults($plugins->getAdditionalActions());
		$modelActions = array_merge($modelActions, $extraActions);

		foreach($modelActions as $action => $label)
		{
			if($model->getAction($action) == false || in_array(true, $plugins->blockAction($action)))
				continue;

			$browseUrl = clone $url;
			$browseUrl->action = $action;
			$menu->addItem($action, $browseUrl, $label);
		}

		$menu->setMenu('add_models');
		$menu->setMenuLabel('Add New');

		$listOfAllowedModels = $model->getAllowedChildrenTypes();

		$addUrlBase = clone $url;
		$addUrlBase->action = 'Add';

		foreach($listOfAllowedModels as $modelType)
		{
			$addUrl = clone $addUrlBase;
			$addUrl->type = $modelType;
			$menu->addItem($modelType, $addUrl, ucfirst($modelType));
		}
	}

	protected function modelToHtml($page, $model, $templateName)
	{
		$theme = $page->getTheme();
		$view = new ViewModelTemplate($theme, $model, $templateName);
		$htmlConverter = $model->getModelAs('Html');
		$htmlConverter->useView($view);
		return $htmlConverter->getOutput();
	}



	/*
	public function viewAdmin()
	{

	}

	public function viewHtml()
	{

	}

	public function viewXml()
	{

	}

	public function viewJson()
	{

	}
	*/
}

?>