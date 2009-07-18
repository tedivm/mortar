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
		$template = $theme->getModelTemplate($templateName, $model->getType());
		$htmlConverter = $model->getModelAs('Html');
		$htmlConverter->useTemplate($template);
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