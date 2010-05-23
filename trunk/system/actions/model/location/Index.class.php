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
class ModelActionLocationBasedIndex extends ModelActionIndex
{
	protected $listingClass = 'LocationListing';

	/**
	 * If this $query['browseBy'] option isn't set this column is used to sort the models.
	 *
	 * @var string
	 */
	public $indexBrowseBy = 'name';

	/**
	 * This function loads the requested models into the childModels properly for us by the various output functions.
	 *
	 */
	public function logic()
	{
		$lastModified = $this->model->getLocation()->getLastModified();
		$this->loadOffsets();
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
	 * This function initiates and sets up the Listing class used by the getChildren class. When overloading this class
	 * this function is an ideal starting place.
	 *
	 * @return LocationListing
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

	public function viewControl($page)
	{
		$indexList = $this->getDisplayList($this->adminSettings['listType']);
		$indexList->setColumns(array('type' => 'Type', 'name' => 'Name', 'title' => 'Title'));

		$indexList->addPage($page);

		return $indexList->getListing();
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
			$rss = new ViewModelRssFeed($this->childModels, $this->model);
			$rss->addChannelElement('lastBuildDate', $this->lastModified);
			$this->ioHandler->addHeader('Content-Type', 'application/rss+xml; charset=utf-8');
			return $rss->getDisplay();
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

		$this->ioHandler->addHeader('Last-Modified', gmdate(HTTP_DATE, $modifiedDate));

		if(isset($this->cacheExpirationOffset) && !isset($this->ioHandler->cacheExpirationOffset))
			$this->ioHandler->cacheExpirationOffset = $this->cacheExpirationOffset;
	}
}

?>