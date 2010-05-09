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

		$this->ioHandler->addHeader('Last-Modified', gmdate(HTTP_DATE, $modifiedDate));

		if(isset($this->cacheExpirationOffset) && !isset($this->ioHandler->cacheExpirationOffset))
			$this->ioHandler->cacheExpirationOffset = $this->cacheExpirationOffset;
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