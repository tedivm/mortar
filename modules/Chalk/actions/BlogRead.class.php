<?php

class ChalkActionBlogRead extends ModelActionLocationBasedIndex
{
        public $adminSettings = array();

	public function viewAdmin($page)
	{
		if(isset($this->model['title']))
			$page->addRegion('pagetitle', htmlentities($this->model['title']));

		return parent::viewHtml($page);
	}
	
	protected function getModelListingClass()
	{
		$locationListing = parent::getModelListingClass();
		$locationListing->setOption('browseBy', 'publishDate');
		$locationListing->setOption('order', 'DESC');
		return $locationListing;
	}
}

?>