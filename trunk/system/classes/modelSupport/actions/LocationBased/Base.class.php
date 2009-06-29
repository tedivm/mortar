<?php
/**
 * BentoBase
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

	/**
	 * This function sends along the Last-Modified headers, and if $this->cacheExpirationOffset is set it also sends
	 * that to the ioHandler. This is vital for client side http caching
	 *
	 * @access protected
	 */
	protected function setHeaders()
	{
		$location = $this->model->getLocation();
		$modifiedDate = strtotime($location->getLastModified());
		$this->ioHandler->addHeader('Last-Modified', gmdate('D, d M y H:i:s T', $modifiedDate));

		if(isset($this->cacheExpirationOffset) && !isset($this->ioHandler->cacheExpirationOffset))
			$this->ioHandler->cacheExpirationOffset = $this->cacheExpirationOffset;

	}


	/**
	 * This creates the permission object and saves it. This function can be overwritten for special purposes, such as
	 * with the Add class which needs to check the parent models permission, not the current model.
	 *
	 */
	protected function setPermissionObject()
	{
		$user = ActiveUser::getUser();
		$this->permissionObject = new Permissions($this->model->getLocation(), $user);
	}

	/**
	 * This function adds new actions and settings to a menu based off of the passed model.
	 *
	 * @param NavigationMenu $menu
	 * @param LocationModel $model
	 * @param string $format
	 */
	protected function makeModelActionMenu($menu, $model, $format = 'Html')
	{
		$plugins = new Hook();
		$plugins->loadModelPlugins($model, 'actionMenu');
		$plugins->addToMenu($menu, $model, $format);

		$menu->setMenu('models_actions');
		$menu->setMenuLabel($model->getType());

		$location = $model->getLocation();

		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->format = $format;

		$modelActions = array('Edit' => 'Edit', 'Delete' => 'Delete');

		if($location->hasChildren())
			$modelActions['Index'] = 'Browse';

		foreach($modelActions as $action => $label)
		{
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
		if(!isset($this->indexModelProperties[$model->getType()]))
		{
			$properties = array(
							array('name' => 'content', 'permission' => 'Read'),
							array('name' => 'title', 'permission' => 'Read'));
		}else{
			$properties = $this->indexModelProperties[$model->getType()];
		}

		$cache = new Cache('models', $model->getType(), 'templates', $templateName);
		$template = $cache->getData();

		if($cache->isStale())
		{
			$template = $theme->getModelTemplate($templateName, $model->getType());
			$cache->storeData($template);
		}

		$modelDisplay = new DisplayMaker();
		$modelDisplay->setDisplayTemplate($template);

		$user = ActiveUser::getUser();
		$permission = new Permissions($model->getLocation(), $user->getId());

		foreach($properties as $property)
		{
			try {
				if(!isset($property['name']))
					continue;

				if(isset($property['permission']) && !$permission->isAllowed($property['permission']))
					continue;

				if(isset($model[$property['name']]))
					$modelDisplay->addContent('model_' . $property['name'], $model[$property['name']]);

			}catch(Exception $e){
				// if one property fails we don't want to abort the listing
			}
		}

		$location = $model->getLocation();
		$url = new Url();
		$url->location = $location->getId();

		$query = Query::getQuery();
		$url->format = $query['format'];

		$modelDisplay->addContent('permalink', (string) $url);

		$userId = $location->getOwner();

		if(is_numeric($userId))
		{
			$user = ModelRegistry::loadModel('User', $userId);
			$modelDisplay->addContent('model_owner', $user['name']);
		}

		$modelDisplay->addDate('model_creationTime', $location->getCreationDate());
		$modelDisplay->addDate('model_lastModified', $location->getLastModified());
		$modelDisplay->addContent('model_name', $location->getName());

		$modelOutput = $modelDisplay->makeDisplay(true);

		return $modelOutput;
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