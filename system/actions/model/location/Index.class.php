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
	protected $getAs = 'HtmlLocationList';

	public function viewControl($page)
	{
		$htmlConverter = $this->model->getModelAs('HtmlList', $template);
		$htmlConverter->setListType($listType);
		$htmlConverter->paginate($paginate);
		$htmlConverter->setColumns(array('type' => 'Type', 'name' => 'Name', 'title' => 'Title'));

		return $htmlConveter->getOutput();
	}

	/**
	 * This will convert the model into RSS for outputting.
	 *
	 * @return string Rss
	 */
	public function viewRss()
	{
		$htmlConverter = $this->model->getModelAs('HtmlList');
		$childModels = $htmlConverter->getChildrenList();

		if(count($childModels) > 0)
		{
			$rss = new ViewModelRssFeed($childModels, $this->model);
			$rss->addChannelElement('lastBuildDate', $this->lastModified);
			$this->ioHandler->addHeader('Content-Type', 'application/rss+xml; charset=utf-8');
			return $rss->getDisplay();
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