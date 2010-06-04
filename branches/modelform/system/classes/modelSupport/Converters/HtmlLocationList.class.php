<?php

class ModelToHtmlLocationList extends ModelToHtmlList
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
	protected function process()
	{
		if(!$this->recursive) {
			$lastModified = $this->model->getLocation()->getLastModified();
			$this->loadOffsets();
			$modelInformationArray = $this->getChildren();
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

		foreach($this->restrictions as $restrictionName => $restrictionValue)
			$listingObject->addRestriction($restrictionName, $restrictionValue);

		foreach($this->options as $optionName => $optionValue)
			$listingObject->setOption($optionName, $optionValue);

		$query = Query::getQuery();

		if(isset($query['status']))
			$listingObject->addRestriction('resourceStatus', $query['status']);

		$listingObject->addRestriction('parent', $this->model->getLocation()->getId());

		if(isset($query['browseBy'])) {
			if($query['browseBy'] === 'date') {
				$listingObject->setOption('browseBy', 'publishDate');
			} else {
				$listingObject->setOption('browseBy', $query['browseBy']);
			}
		} elseif(!isset($this->options['browseBy'])) { 
			$listingObject->setOption('browseBy', $this->indexBrowseBy);
		}

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

}

?>