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
	protected $listingClass = 'LocationListing';

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
		$lastModified = $this->model->getLocation()->getLastModified();
		$modelInformationArray = $this->getChildren(array());
		$childrenModels = array();
		if(is_array($modelInformationArray))
		{
			foreach($modelInformationArray as $modelInfo)
			{
				$childModel = ModelRegistry::loadModel($modelInfo['type'], $modelInfo['id']);
				$childrenModels[] = $childModel;
				$location = $childModel->getLocation();
				$creation = $location->getCreationDate();
				$modification = $location->getLastModified();
				$lastModified = ($lastModified > $modification) ? $lastModified : $modification;
			}
		}

		$this->lastModified = $lastModified;
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
		$listingClass = $this->listingClass;
		$listingObject = new $listingClass();

		$query = Query::getQuery();

		$browseBy = (isset($query['browseBy'])) ? $query['browseBy'] : $this->indexBrowseBy;
		$listingObject->setOption('browseBy', $browseBy);

		if(isset($query['status']))
			$listingObject->addRestriction('resourceStatus', $query['status']);

		$listingObject->addRestriction('parent', $this->model->getLocation()->getId());

		if(isset($query['browseBy']))
			if($query['browseBy'] === 'date')
				$listingObject->setOption('browseBy', 'publishDate');
			else
				$listingObject->setOption('browseBy', $query['browseBy']);
		else
			$listingObject->setOption('browseBy', 'name');

		if(isset($query['order']))
			$listingObject->setOption('order', $query['order']);

		if(isset($query['day']))
			$listingObject->addFunction('publishDate', 'day', $query['day']);

		if(isset($query['month']))
			$listingObject->addFunction('publishDate', 'month', $query['month']);

		if(isset($query['year']))
			$listingObject->addFunction('publishDate', 'year', $query['year']);

		return $listingObject;
	}

	protected function getIndexDisplayList()
	{
		$indexList = new IndexDisplayList($this->model, $this->childModels);
		return $indexList;
	}

	protected function getReadDisplayList()
	{
		$readList = new ReadDisplayList($this->model, $this->childModels);
		return $readList;
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

		$indexList = $this->getIndexDisplayList();
		$indexList->addPage($page);

		return $indexList->getListing();
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
		$readList = $this->getReadDisplayList();
		$readList->addPage($page);

		if($listingResults = $readList->getListing())
			$output .= $listingResults;

		return $output;
	}


	/**
	 * This will convert the model into RSS for outputting.
	 *
	 * @return string Rss
	 */
	public function viewRss()
	{
		if(count($this->childModels) > 0)
		{
			$Rss = new ViewModelRssFeed($this->childModels, $this->model);
			return $Rss->getDisplay();
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