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
 * This class acts as the default 'read' action for any model. It is ridiculous simple, as all the heavy lifting is done
 * by the ModelActionBase class.
 *
 * @package System
 * @subpackage ModelSupport
 */
class ModelActionLocationBasedIndex extends ModelActionLocationBasedRead
{
	/**
	 * This is the date format used when converting the model to an html table.
	 *
	 * @var string
	 */
	protected $indexDateFormat = 'm.d.y g:i a';

	/**
	 * If this $query['browseBy'] option isn't set this column is used to sort the models.
	 *
	 * @var string
	 */
	public $indexBrowseBy = 'name';

	/**
	 * This is the maximum number of models a user can request at one time.
	 *
	 * @var int
	 */
	public $indexMaxLimit = 100;

	/**
	 * This is the default number of models returned if the user does not specify how many they want.
	 *
	 * @var int
	 */
	public $indexLimit = 10;

	/**
	 * This array contains the models requested by the user.
	 *
	 * @var array
	 */
	public $childModels = array();

	/**
	 * This function loads the requested models into the childModels properly for us by the various output functions.
	 *
	 */
	public function logic()
	{
		$modelInformationArray = $this->getChildren(array());
		$childrenModels = array();
		if(is_array($modelInformationArray))
			foreach($modelInformationArray as $modelInfo)
		{
			$childrenModels[] = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);
		}

		$this->childModels = $childrenModels;
	}

	/**
	 * This function ties the user input into a Listing class retrieved from getModelListingClass() and returns the
	 * models to the logic function.
	 *
	 * @param array $restrictions
	 * @return array Contains keys 'type' and 'id'
	 */
	protected function getChildren($restrictions)
	{
		$query = Query::getQuery();

		$offset = isset($query['start']) ? $query['start'] : 0;
		$numberChildren = isset($query['limit']) && is_numeric($query['limit'])
							? $query['limit']
							: $this->indexLimit;

		if($numberChildren > $this->indexMaxLimit)
			$numberChildren = $this->indexMaxLimit;

		$modelListing = $this->getModelListingClass();

		foreach($restrictions as $restrictionName => $restrictionValue)
			$modelListing->addRestriction($restrictionName, $restrictionValue);


		$listing = $modelListing->getListing($numberChildren, $offset);
		return $listing;
	}

	/**
	 * This function initiates and sets up the Listing class used by the getChildren class. When overloading this class
	 * this function is an ideal starting place.
	 *
	 * @return ModelListing
	 */
	protected function getModelListingClass()
	{
		$locationListing = new LocationListing();

		$query = Query::getQuery();

		$browseBy = (isset($query['browseBy'])) ? $query['browseBy'] : $this->indexBrowseBy;
		$locationListing->setOption('browseBy', $browseBy);

		$locationListing->addRestriction('parent', $this->model->getLocation()->getId());

		if(isset($query['order']))
			$locationListing->setOption('order', $query['order']);

		return $locationListing;
	}

	/**
	 * Creates a listing of models along with relevant qualities and actions for use in an admin page.
	 *
	 * @return string
	 */
	public function viewAdmin($page)
	{
		$menu = $page->getMenu('actions', 'modelNav');
		$this->makeModelActionMenu($menu, $this->model, 'Admin');

		$theme = $page->getTheme();

		$rowPolarity = false;
		$modelData = '';
		foreach($this->childModels as $model)
		{
			$rowPolarity = (isset($rowPolarity) && 'even') ? 'odd' : 'even';
			$modelData .= $this->makeModelListEntry($model, $theme, $rowPolarity);
		}

		$modelListTable = new DisplayMaker();
		$modelListTable->setDisplayTemplate($theme->getModelTemplate("ModelListTable.html", $model->getType()));

		$name = isset($page['title']) ? preg_replace('/\s/', '', $page['title']) : "no-name";
		$modelListTable->addContent('id', $name . "-admin-table");
		$modelListTable->addContent('class', 'modellist-table');
		$modelListTable->addContent('table-contents', $modelData);
		return $modelListTable->makeDisplay();
	}

	/**
	 * Generates a list entry for a model for inclusion in an admin page listing.
	 *
	 * @param Model $model
	 * @param Theme $theme
	 * @param String $evenodd
	 */
	public function makeModelListEntry($model, $theme, $evenodd)
	{
		$modelListLine = new DisplayMaker();
		$modelListLine->setDisplayTemplate($theme->getModelTemplate("ModelListLine.html", $model->getType()));
		$baseUrl = new Url();
		if(method_exists($model, 'getLocation'))
		{
			$location = $model->getLocation();
			$baseUrl->locationId = $location->getId();
			$modelListLine->addContent('name', $location->getName());
			$modelListLine->addContent('creation', date($this->indexDateFormat, $location->getCreationDate()));
		} else
		{
			$baseUrl->type = $model->getType();
			$baseUrl->id = $model->getId();

			$name = isset($model['name']) ? $model['name'] : isset($model['title']) ? $model['title'] : false;
			if($name)
				$modelListLine->addContent('name', $name);
				$modelListLine->addContent('creation', '');
		}
		$modelListLine->addContent('evenodd', $evenodd);

		$baseUrl->format = 'Admin';

		$actionTypes = array('Read', 'Edit', 'Delete');

		$location = $model->getLocation();
		if(isset($location) && $location->hasChildren())
			array_unshift($actionTypes, 'Index');


		$user = ActiveUser::getUser();
		$userId = $user->getId();
		$actionList = '';
		foreach($actionTypes as $action)
		{
			$modelListAction = new DisplayMaker();
			$modelListAction->setDisplayTemplate($theme->getModelTemplate("ModelListAction.html", $model->getType()));
			$actionUrl = clone $baseUrl;
			$actionUrl->action = $action;

			if($actionUrl->checkPermission($userId))
			{
				$modelListAction->addContent('action', $actionUrl->getLink(ucfirst($action)));
				$actionList .= $modelListAction->makeDisplay();
			}

		}
		$modelListLine->addContent('actions', $actionList);

		return $modelListLine->makeDisplay();
	}

	/**
	 * This function takes the model's data and puts it into a template, which gets injected into the active page. It
	 * also takes out some model data to place in the rest of the template (title, keywords, descriptions).
	 *
	 * @return string This is the html that will get injected into the template.
	 */
	public function viewHtml($page)
	{
		$output = parent::viewHtml($page);
		$output .= $this->childrenToHtml($page, 'Listing.html');
		return $output;
	}

	protected function childrenToHtml($page, $templateName)
	{
		if(count($this->childModels) > 0)
		{
			$listingHtml = new HtmlObject('div');
			$listingHtml->property('name', 'listing-container');
			$theme = $page->getTheme();
			$templates = array();
			$x = 1;

			foreach($this->childModels as $model)
			{
				$type = $model->getType();

				if($modelDisplay = $this->modelToHtml($page, $model, 'Listing.html'))
				{
					$listingHtml->insertNewHtmlObject('div')->
						property('name', 'listing-container-child-' . $x)->
						addClass('modelListing')->addClass($type)->
						wrapAround($modelDisplay);
					$x++;
				}
			}
			$output = (string) $listingHtml;
		}
		return $output;
	}




	/**
	 * This will convert the model into XML for outputting.
	 *
	 * @return string XML
	 */
	public function viewXml()
	{
		if(count($this->childModels) > 0)
		{

		}else{

		}
	}

	/**
	 * This takes the model and turns it into an array. The output controller converts that to json, which gets
	 * outputted.
	 *
	 * @return array
	 */
	public function viewJson()
	{
		$children = array();
		if(count($this->childModels) > 0)
		{
			foreach($this->childModels as $model)
			{
				$children[] = $model->__toArray();
			}
			return $children;
		}else{
			return false;
		}
	}
}

?>