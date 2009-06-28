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
 * This class acts as the default 'read' action for any model. It is ridiculous simple, as all the heavy lifting is done
 * by the ModelActionBase class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedRead extends ModelActionLocationBasedBase
{

	/**
	 * This literally does nothing at all.
	 *
	 */
	public function logic()
	{

	}


	/**
	 * This is incredibly basic right now, but thats because I'm working woth the Joshes on getting the interface
	 * for it set up.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		$menu = $page->getMenu('actions', 'modelNav');
		$this->makeModelActionMenu($menu, $this->model);
		return $this->modelToHtml($page, $this->model, 'Display.html');
	}

	/**
	 * This function adds new actions and settings to a menu based off of the passed model.
	 *
	 * @param NavigationMenu $menu
	 * @param LocationModel $model
	 */
	protected function makeModelActionMenu($menu, $model)
	{
		$menu->setMenuLabel('Add New');
		$listOfAllowedModels = $model->getAllowedChildrenTypes();
		$url = new Url();
		$url->location = $model->getLocation()->getId();
		$url->action = 'Add';

		foreach($listOfAllowedModels as $modelType)
		{
			$addUrl = clone $url;
			$addUrl->type = $modelType;
			$menu->addItem($modelType, $addUrl, ucfirst($modelType));
		}
	}



	/**
	 * This function takes the model's data and puts it into a template, which gets injected into the active page. It
	 * also takes out some model data to place in the rest of the template (title, keywords, descriptions).
	 *
	 * @return string This is the html that will get injected into the template.
	 */
	public function viewHtml($page)
	{
		if(isset($this->model['title']))
			$page->addRegion('title', htmlentities($this->model['title']));

		return $this->modelToHtml($page, $this->model, 'Display.html');
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



	/**
	 * This will convert the model into XML for outputting.
	 *
	 * @return string XML
	 */
	public function viewXml()
	{
		$xml = ModelToXml::convert($this->model, $this->requestHandler);
		return $xml;
	}

	/**
	 * This takes the model and turns it into an array. The output controller converts that to json, which gets
	 * outputted.
	 *
	 * @return array
	 */
	public function viewJson()
	{
		$array = ModelToArray::convert($this->model, $this->requestHandler);
		return $array;
	}
}

?>